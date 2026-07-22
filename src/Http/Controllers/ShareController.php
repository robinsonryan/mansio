<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use RobinsonRyan\Mansio\Actions\RecordAccess;
use RobinsonRyan\Mansio\Contracts\ContentStore;
use RobinsonRyan\Mansio\Contracts\PreviewGenerator;
use RobinsonRyan\Mansio\Contracts\Shareable;
use RobinsonRyan\Mansio\Events\ShareUnlockFailed;
use RobinsonRyan\Mansio\Exceptions\ShareNotAccessible;
use RobinsonRyan\Mansio\Exceptions\UnlockRequired;
use RobinsonRyan\Mansio\Http\Middleware\ResolveShareMiddleware;
use RobinsonRyan\Mansio\Mansio;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Models\ShareEvent;
use RobinsonRyan\Mansio\Models\Version;
use RobinsonRyan\Mansio\Support\ShareContext;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Thin HTTP wrapper over the {@see Mansio} manager. The share is bound by
 * {@see ResolveShareMiddleware}; guard evaluation
 * runs here so a challenge can render the unlock form rather than 404.
 */
final class ShareController
{
    public function __construct(
        private readonly Mansio $mansio,
        private readonly RecordAccess $recordAccess,
        private readonly ContentStore $store,
    ) {}

    public function show(Request $request, string $token): View|RedirectResponse
    {
        $share = $this->share($request);

        try {
            $share = $this->mansio->resolve($token, $this->context($request, $share));
        } catch (UnlockRequired $e) {
            return $this->renderUnlock($token, $e->challengeType);
        } catch (ShareNotAccessible) {
            abort(404);
        }

        $version = $share->serveableVersion();

        $this->recordAccess->handle($share, ShareEvent::VIEWED, [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'version_id' => $version?->getKey(),
        ]);

        // @phpstan-ignore argument.type (package-namespaced view, not resolvable at analysis time)
        return view('mansio::show', [
            'title' => $this->title($share),
            'currentVersion' => $version,
            'downloadUrl' => route('mansio.download', $share->token),
            'changelog' => $this->changelog($share),
        ]);
    }

    public function download(Request $request, string $token): StreamedResponse|RedirectResponse
    {
        $share = $this->share($request);

        try {
            $share = $this->mansio->resolve($token, $this->context($request, $share));
        } catch (UnlockRequired) {
            return redirect()->route('mansio.show', $share->token);
        } catch (ShareNotAccessible) {
            abort(404);
        }

        $version = $share->serveableVersion();

        if ($version === null) {
            abort(404);
        }

        $this->recordAccess->handle($share, ShareEvent::DOWNLOADED, [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'version_id' => $version->getKey(),
        ]);

        return $this->streamVersion($share, $version, disposition: 'attachment');
    }

    public function preview(Request $request, string $token, ?int $seq = null): StreamedResponse|RedirectResponse
    {
        $share = $this->share($request);

        try {
            $share = $this->mansio->resolve($token, $this->context($request, $share));
        } catch (UnlockRequired) {
            return redirect()->route('mansio.show', $share->token);
        } catch (ShareNotAccessible) {
            abort(404);
        }

        $version = $seq !== null
            ? $this->versionAtSequence($share, $seq)
            : $share->serveableVersion();

        if ($version === null) {
            abort(404);
        }

        if (app()->bound(PreviewGenerator::class)) {
            $path = app(PreviewGenerator::class)->preview($version);

            if ($path !== null) {
                return $this->streamPath($path, $version->mime, $this->filename($share, $version), 'inline');
            }
        }

        return $this->streamVersion($share, $version, disposition: 'inline');
    }

    public function unlock(Request $request, string $token): RedirectResponse|View
    {
        $share = $this->share($request);

        $key = 'mansio-unlock:' . $share->getKey() . '|' . $request->ip();
        $maxAttempts = (int) config('mansio.password.throttle.max_attempts', 5);
        $decaySeconds = (int) config('mansio.password.throttle.decay_minutes', 10) * 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return $this->renderUnlock($token, 'password', __('Too many attempts. Please try again later.'));
        }

        $credentials = [
            'password' => $request->input('password'),
            'otp' => $request->input('otp'),
        ];

        try {
            $share = $this->mansio->resolve($token, $this->context($request, $share, $credentials));
        } catch (UnlockRequired $e) {
            RateLimiter::hit($key, $decaySeconds);
            $this->recordFailure($share, $request, $e->challengeType);

            return $this->renderUnlock($token, $e->challengeType, __('Please provide the required credentials.'));
        } catch (ShareNotAccessible $e) {
            RateLimiter::hit($key, $decaySeconds);
            $this->recordFailure($share, $request, $this->challengeFromReason($e->getMessage()));

            return $this->renderUnlock($token, $this->challengeFromReason($e->getMessage()), __('That was incorrect. Please try again.'));
        }

        RateLimiter::clear($key);

        session(['mansio_unlocked.' . $share->getKey() => true]);

        $this->recordAccess->handle($share, ShareEvent::UNLOCK_SUCCESS, [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->route('mansio.show', $share->token);
    }

    private function share(Request $request): Share
    {
        $share = $request->attributes->get('mansioShare');

        if (! $share instanceof Share) {
            abort(404);
        }

        return $share;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function context(Request $request, Share $share, array $credentials = []): ShareContext
    {
        return new ShareContext(
            $share,
            $request->ip(),
            $request->userAgent(),
            $credentials,
        );
    }

    private function recordFailure(Share $share, Request $request, string $type): void
    {
        $this->recordAccess->handle($share, ShareEvent::UNLOCK_ATTEMPT, [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'challenge' => $type,
        ]);

        event(new ShareUnlockFailed($share, $type));
    }

    private function challengeFromReason(string $reason): string
    {
        return str_contains($reason, 'otp') ? 'otp' : 'password';
    }

    private function renderUnlock(string $token, string $challengeType, ?string $error = null): View
    {
        // @phpstan-ignore argument.type (package-namespaced view, not resolvable at analysis time)
        return view('mansio::unlock', [
            'token' => $token,
            'challengeType' => $challengeType,
            'error' => $error,
        ]);
    }

    private function title(Share $share): string
    {
        $shareable = $share->shareable;

        if ($shareable instanceof Shareable) {
            return $shareable->mansioTitle();
        }

        return $share->label ?? 'Shared document';
    }

    /**
     * @return array<int, array{sequence: int, summary: string|null, published_at: mixed}>
     */
    private function changelog(Share $share): array
    {
        /** @var class-string<Version> $versionClass */
        $versionClass = config('mansio.models.version', Version::class);

        return $versionClass::query()
            ->where('shareable_type', $share->shareable_type)
            ->where('shareable_id', $share->shareable_id)
            ->orderByDesc('sequence')
            ->get()
            ->map(fn (Version $version): array => [
                'sequence' => $version->sequence,
                'summary' => $version->summary,
                'published_at' => $version->published_at,
            ])
            ->all();
    }

    private function versionAtSequence(Share $share, int $sequence): ?Version
    {
        /** @var class-string<Version> $versionClass */
        $versionClass = config('mansio.models.version', Version::class);

        /** @var Version|null $version */
        $version = $versionClass::query()
            ->where('shareable_type', $share->shareable_type)
            ->where('shareable_id', $share->shareable_id)
            ->where('sequence', $sequence)
            ->first();

        return $version;
    }

    private function streamVersion(Share $share, Version $version, string $disposition): StreamedResponse
    {
        return $this->streamPath(
            $version->content_path,
            $version->mime,
            $this->filename($share, $version),
            $disposition,
        );
    }

    private function streamPath(string $path, string $mime, string $filename, string $disposition): StreamedResponse
    {
        $store = $this->store;

        return response()->stream(function () use ($store, $path): void {
            $stream = $store->stream($path);
            fpassthru($stream);

            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function filename(Share $share, Version $version): string
    {
        $base = Str::slug($this->title($share)) ?: 'document';

        return $base . '-v' . $version->sequence . '.' . $this->extensionForMime($version->mime);
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'text/html' => 'html',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/webp' => 'webp',
            'application/zip' => 'zip',
            'text/plain' => 'txt',
            'application/json' => 'json',
            default => 'bin',
        };
    }
}
