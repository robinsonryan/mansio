<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Support;

use RobinsonRyan\Mansio\Contracts\AccessGuard;

/**
 * The outcome of an {@see AccessGuard}: pass, deny
 * (with a reason), or challenge (e.g. password / OTP, short-circuiting to unlock).
 */
final class GuardResult
{
    public const PASS = 'pass';

    public const DENY = 'deny';

    public const CHALLENGE = 'challenge';

    private function __construct(
        public readonly string $outcome,
        public readonly ?string $reason = null,
        public readonly ?string $challengeType = null,
    ) {}

    public static function pass(): self
    {
        return new self(self::PASS);
    }

    public static function deny(string $reason): self
    {
        return new self(self::DENY, reason: $reason);
    }

    public static function challenge(string $type): self
    {
        return new self(self::CHALLENGE, challengeType: $type);
    }

    public function passed(): bool
    {
        return $this->outcome === self::PASS;
    }

    public function denied(): bool
    {
        return $this->outcome === self::DENY;
    }

    public function isChallenge(): bool
    {
        return $this->outcome === self::CHALLENGE;
    }
}
