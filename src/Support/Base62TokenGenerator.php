<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Support;

use RobinsonRyan\Mansio\Contracts\TokenGenerator;

/**
 * Default token strategy: cryptographically-random base62 of a configurable length.
 */
final class Base62TokenGenerator implements TokenGenerator
{
    private const ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    public function __construct(private readonly int $length = 32) {}

    public function generate(): string
    {
        $alphabetSize = strlen(self::ALPHABET);
        $token = '';

        for ($i = 0; $i < $this->length; $i++) {
            $token .= self::ALPHABET[random_int(0, $alphabetSize - 1)];
        }

        return $token;
    }
}
