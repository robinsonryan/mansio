<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Guards;

use Illuminate\Support\Facades\Hash;
use RobinsonRyan\Mansio\Contracts\AccessGuard;
use RobinsonRyan\Mansio\Support\GuardResult;
use RobinsonRyan\Mansio\Support\ShareContext;

/**
 * Gates a password-protected share. Passes freely when no password is set or the
 * share has already been unlocked in this session; otherwise verifies a submitted
 * password credential, or challenges for one when none was provided.
 */
final class PasswordGuard implements AccessGuard
{
    public function check(ShareContext $context): GuardResult
    {
        $share = $context->share;

        if ($share->password_hash === null) {
            return GuardResult::pass();
        }

        if (session()->get('mansio_unlocked.' . $share->id) === true) {
            return GuardResult::pass();
        }

        $password = $context->credential('password');

        if ($password !== null) {
            return Hash::check((string) $password, $share->password_hash)
                ? GuardResult::pass()
                : GuardResult::deny('bad_password');
        }

        return GuardResult::challenge('password');
    }
}
