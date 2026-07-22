<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Support;

use RobinsonRyan\Mansio\Models\Share;

/**
 * The per-hit evaluation context passed to every guard: the resolved share plus the
 * request-derived signals (IP, user agent) and any submitted credentials
 * (password, OTP code, …).
 */
final class ShareContext
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function __construct(
        public readonly Share $share,
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null,
        public readonly array $credentials = [],
    ) {}

    public function credential(string $key): mixed
    {
        return $this->credentials[$key] ?? null;
    }
}
