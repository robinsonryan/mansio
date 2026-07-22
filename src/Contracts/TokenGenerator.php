<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Contracts;

/**
 * Link token strategy. The default emits an unguessable 32-char base62 string.
 */
interface TokenGenerator
{
    public function generate(): string;
}
