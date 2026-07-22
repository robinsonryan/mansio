<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use RobinsonRyan\Mansio\Concerns\HasUuid7PrimaryKey;
use RobinsonRyan\Mansio\Concerns\IsShareable;
use RobinsonRyan\Mansio\Contracts\Shareable;

/**
 * A shareable that belongs to a {@see TestOwner}. Reuses the shareables table but
 * returns an owner from {@see mansioOwner()} so owner-scoping (R18) can be proven.
 */
final class OwnedShareable extends Model implements Shareable
{
    use HasUuid7PrimaryKey;
    use IsShareable;

    protected $table = 'mansio_test_shareables';

    protected $guarded = [];

    /**
     * The owner to report. Declared as a typed property so Eloquent does not treat
     * it as a persisted attribute.
     */
    public ?TestOwner $ownerModel = null;

    public function mansioOwner(): ?object
    {
        return $this->ownerModel;
    }
}
