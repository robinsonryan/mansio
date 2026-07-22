<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Exceptions;

use RuntimeException;

/**
 * Raised by the guard pipeline when a guard denies access. Carries the machine
 * reason for auditing; callers decide how (and whether) to surface it.
 */
final class GuardDenied extends RuntimeException
{
    public function __construct(public readonly string $guardReason)
    {
        parent::__construct($guardReason);
    }
}
