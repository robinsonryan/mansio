<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Exceptions;

use RobinsonRyan\Mansio\Models\Share;
use RuntimeException;

/**
 * Raised when a guard short-circuits with a challenge (e.g. password / OTP). The
 * controller catches this to render the unlock flow rather than a 404 — the share
 * exists and is otherwise servable, it just needs a credential.
 */
final class UnlockRequired extends RuntimeException
{
    public function __construct(
        public readonly string $challengeType,
        public readonly Share $share,
    ) {
        parent::__construct('mansio: unlock required (' . $challengeType . ')');
    }
}
