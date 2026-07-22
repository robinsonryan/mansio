<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use RobinsonRyan\Mansio\Concerns\HasUuid7PrimaryKey;

/**
 * A stand-in tenant/account model used to prove Mansio's optional owner scoping
 * (R18) — a distinct morph type recorded on shares minted for an owned shareable.
 */
final class TestOwner extends Model
{
    use HasUuid7PrimaryKey;

    protected $table = 'mansio_test_owners';

    protected $guarded = [];
}
