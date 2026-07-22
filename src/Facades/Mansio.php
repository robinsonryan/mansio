<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Facades;

use Illuminate\Support\Facades\Facade;
use RobinsonRyan\Mansio\Contracts\Shareable;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Models\Version;
use RobinsonRyan\Mansio\Support\PendingShareable;
use RobinsonRyan\Mansio\Support\ShareContext;

/**
 * @method static PendingShareable for(Shareable $shareable)
 * @method static Share resolve(string $token, ShareContext|array<string, mixed> $context = [])
 * @method static Share revoke(Share $share)
 * @method static Version rollback(Shareable $shareable, int $toSequence)
 *
 * @see \RobinsonRyan\Mansio\Mansio
 */
final class Mansio extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'mansio';
    }
}
