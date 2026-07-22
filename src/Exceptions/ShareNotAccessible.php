<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Exceptions;

use RuntimeException;

/**
 * Thrown when a share cannot be served: unknown/revoked/expired token, or a guard
 * denial. Controllers translate this to a 404 — never leaking whether a token
 * existed.
 */
final class ShareNotAccessible extends RuntimeException
{
    public static function reason(string $reason): self
    {
        return new self($reason);
    }
}
