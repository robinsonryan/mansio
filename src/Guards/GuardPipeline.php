<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Guards;

use RobinsonRyan\Mansio\Contracts\AccessGuard;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Support\GuardResult;
use RobinsonRyan\Mansio\Support\ShareContext;

/**
 * Runs an ordered set of {@see AccessGuard}s against a {@see ShareContext}. The
 * first non-pass result short-circuits; if every guard passes the share is served.
 * The default guard list comes from config, with per-share appends via settings.
 */
final class GuardPipeline
{
    /**
     * @param  array<int, class-string<AccessGuard>>  $guards
     */
    public function __construct(
        private array $guards = [],
    ) {
        if ($this->guards === []) {
            $this->guards = config('mansio.guards', []);
        }
    }

    /**
     * @return array<int, class-string<AccessGuard>>
     */
    public function resolveGuards(Share $share): array
    {
        $overrides = $share->settings['guards'] ?? [];

        if (! is_array($overrides)) {
            $overrides = [];
        }

        return array_values(array_merge($this->guards, $overrides));
    }

    public function run(ShareContext $context): GuardResult
    {
        foreach ($this->resolveGuards($context->share) as $class) {
            /** @var AccessGuard $guard */
            $guard = app()->make($class);

            $result = $guard->check($context);

            if (! $result->passed()) {
                return $result;
            }
        }

        return GuardResult::pass();
    }
}
