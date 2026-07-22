<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Actions;

use RobinsonRyan\Mansio\Events\ShareExpired;
use RobinsonRyan\Mansio\Exceptions\ShareNotAccessible;
use RobinsonRyan\Mansio\Exceptions\UnlockRequired;
use RobinsonRyan\Mansio\Guards\GuardPipeline;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Models\ShareEvent;
use RobinsonRyan\Mansio\Support\ShareContext;

/**
 * Resolves a token to a servable {@see Share}: looks up the active share (404-safe —
 * an unknown/revoked token simply isn't found) and runs the guard pipeline. A denial
 * becomes {@see ShareNotAccessible}; a challenge becomes {@see UnlockRequired} so the
 * caller can render the unlock flow. Does not record events — {@see RecordAccess} does.
 */
final class ResolveShare
{
    public function __construct(private readonly GuardPipeline $pipeline) {}

    /**
     * @param  ShareContext|array<string, mixed>  $context
     */
    public function handle(string $token, ShareContext|array $context = []): Share
    {
        /** @var class-string<Share> $shareClass */
        $shareClass = config('mansio.models.share', Share::class);

        /** @var Share|null $share */
        $share = $shareClass::query()->active()->where('token', $token)->first();

        if ($share === null) {
            throw ShareNotAccessible::reason('not_found');
        }

        $shareContext = $this->toContext($share, $context);

        $result = $this->pipeline->run($shareContext);

        if ($result->passed()) {
            return $share;
        }

        if ($result->isChallenge()) {
            throw new UnlockRequired((string) $result->challengeType, $share);
        }

        if ($result->reason === 'expired' || $share->isExpired()) {
            $this->expire($share);
        }

        throw ShareNotAccessible::reason((string) $result->reason);
    }

    /**
     * Transition a share to the expired state exactly once: flip its status, write an
     * 'expired' audit event and dispatch {@see ShareExpired}. Idempotent — a share
     * already marked expired never re-fires, and because resolution only ever loads
     * active shares the transition happens on the first post-expiry hit and no later.
     */
    private function expire(Share $share): void
    {
        if ($share->status === 'expired') {
            return;
        }

        $share->forceFill(['status' => 'expired'])->save();

        $share->recordEvent(ShareEvent::EXPIRED);

        event(new ShareExpired($share));
    }

    /**
     * @param  ShareContext|array<string, mixed>  $context
     */
    private function toContext(Share $share, ShareContext|array $context): ShareContext
    {
        if ($context instanceof ShareContext) {
            return new ShareContext(
                $share,
                $context->ip,
                $context->userAgent,
                $context->credentials,
            );
        }

        /** @var array<string, mixed> $credentials */
        $credentials = $context['credentials'] ?? [];

        return new ShareContext(
            $share,
            isset($context['ip']) ? (string) $context['ip'] : null,
            isset($context['user_agent']) ? (string) $context['user_agent'] : (isset($context['userAgent']) ? (string) $context['userAgent'] : null),
            $credentials,
        );
    }
}
