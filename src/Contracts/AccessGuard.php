<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Contracts;

use RobinsonRyan\Mansio\Support\GuardResult;
use RobinsonRyan\Mansio\Support\ShareContext;

/**
 * A single access rule, evaluated on every hit of a share. Guards are composed
 * into an ordered pipeline; the first non-pass result short-circuits.
 */
interface AccessGuard
{
    public function check(ShareContext $context): GuardResult;
}
