<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Guards;

use Illuminate\Support\Facades\Cache;
use RobinsonRyan\Mansio\Contracts\AccessGuard;
use RobinsonRyan\Mansio\Support\GuardResult;
use RobinsonRyan\Mansio\Support\ShareContext;

/**
 * Opt-in "verify it's you" tier. When a share requires OTP, checks a submitted
 * code against the expected code stashed in cache; challenges when absent, denies
 * when wrong. Passes freely when the share does not require OTP.
 */
final class EmailOtpGuard implements AccessGuard
{
    public function check(ShareContext $context): GuardResult
    {
        $share = $context->share;

        $required = (bool) ($share->settings['otp'] ?? false);

        if (! $required) {
            return GuardResult::pass();
        }

        $submitted = $context->credential('otp');

        if ($submitted === null) {
            return GuardResult::challenge('otp');
        }

        $expected = Cache::get('mansio_otp.' . $share->id);

        if ($expected !== null && hash_equals((string) $expected, (string) $submitted)) {
            return GuardResult::pass();
        }

        return GuardResult::deny('bad_otp');
    }
}
