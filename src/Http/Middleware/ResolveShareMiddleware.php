<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use RobinsonRyan\Mansio\Models\Share;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds the `{token}` route parameter to an ACTIVE, existing {@see Share} and stashes
 * it on the request. An unknown or non-active token 404s here — never 403, never a
 * response that would confirm a token ever existed. Guard evaluation (and any unlock
 * challenge) happens downstream in the controller so challenges can render the form.
 */
final class ResolveShareMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = (string) $request->route('token');

        /** @var class-string<Share> $shareClass */
        $shareClass = config('mansio.models.share', Share::class);

        /** @var Share|null $share */
        $share = $shareClass::query()->active()->where('token', $token)->first();

        if ($share === null) {
            abort(404);
        }

        $request->attributes->set('mansioShare', $share);

        return $next($request);
    }
}
