<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Guards\NotExpiredGuard;
use RobinsonRyan\Mansio\Guards\NotRevokedGuard;
use RobinsonRyan\Mansio\Guards\OneTimeGuard;
use RobinsonRyan\Mansio\Guards\PasswordGuard;
use RobinsonRyan\Mansio\Guards\WithinViewLimitGuard;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Models\ShareEvent;
use RobinsonRyan\Mansio\Models\Version;

return [

    /*
    |--------------------------------------------------------------------------
    | Public route mounting
    |--------------------------------------------------------------------------
    | The prefix, middleware, and (optional) domain the public share routes are
    | registered under. The route group intentionally omits the app auth guard —
    | the guard pipeline is the only gate.
    */
    'route' => [
        'prefix' => 'docs',
        'middleware' => ['web'],
        'domain' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content store
    |--------------------------------------------------------------------------
    | Where artifact bytes live. The default Flysystem store writes to the given
    | Laravel filesystem disk under the given path prefix.
    */
    'store' => [
        'driver' => 'flysystem',
        'disk' => env('MANSIO_DISK', 'local'),
        'path' => 'mansio',
    ],

    /*
    |--------------------------------------------------------------------------
    | Token generation
    |--------------------------------------------------------------------------
    */
    'token' => [
        'length' => 32,
    ],

    /*
    |--------------------------------------------------------------------------
    | Primary key type
    |--------------------------------------------------------------------------
    | 'uuid7' relies on a Postgres 18 `uuidv7()` column default (no PHP-side
    | generation). 'incrementing' is available for other engines / tests.
    */
    'id_type' => 'uuid7',

    /*
    |--------------------------------------------------------------------------
    | Owner scoping
    |--------------------------------------------------------------------------
    | When enabled, shares record an optional owner morph (tenant/account) so
    | consuming apps can scope management queries.
    */
    'owner' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Guard pipeline
    |--------------------------------------------------------------------------
    | The default, ordered set of guards evaluated on every hit. Order matters.
    | EmailOtpGuard / IpAllowGuard are opt-in per share via the share `settings`.
    | Apps may register additional guard classes here.
    */
    'guards' => [
        NotRevokedGuard::class,
        NotExpiredGuard::class,
        WithinViewLimitGuard::class,
        OneTimeGuard::class,
        PasswordGuard::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Password unlock throttling
    |--------------------------------------------------------------------------
    */
    'password' => [
        'throttle' => [
            'max_attempts' => 5,
            'decay_minutes' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    | Swap in extended models here; the package resolves these bindings.
    */
    'models' => [
        'share' => Share::class,
        'version' => Version::class,
        'event' => ShareEvent::class,
    ],
];
