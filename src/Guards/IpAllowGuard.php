<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Guards;

use RobinsonRyan\Mansio\Contracts\AccessGuard;
use RobinsonRyan\Mansio\Support\GuardResult;
use RobinsonRyan\Mansio\Support\ShareContext;

/**
 * Opt-in IP allow-list. Denies when the share defines allowed IPs and the request
 * IP is not among them; passes freely when no allow-list is configured.
 */
final class IpAllowGuard implements AccessGuard
{
    public function check(ShareContext $context): GuardResult
    {
        $allowed = $context->share->settings['allowed_ips'] ?? [];

        if (! is_array($allowed) || $allowed === []) {
            return GuardResult::pass();
        }

        if (! in_array($context->ip, $allowed, true)) {
            return GuardResult::deny('ip');
        }

        return GuardResult::pass();
    }
}
