<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use RobinsonRyan\Mansio\Concerns\HasUuid7PrimaryKey;
use RobinsonRyan\Mansio\Concerns\IsShareable;
use RobinsonRyan\Mansio\Contracts\Shareable;

/**
 * A consumer-style model used across the suite to prove the {@see Shareable}
 * contract works with no package → app coupling.
 */
final class TestShareable extends Model implements Shareable
{
    use HasUuid7PrimaryKey;
    use IsShareable;

    protected $table = 'mansio_test_shareables';

    protected $guarded = [];

    public function mansioTitle(): string
    {
        return $this->title ?? 'Untitled';
    }

    public function mansioDefaultMime(): string
    {
        return 'application/pdf';
    }
}
