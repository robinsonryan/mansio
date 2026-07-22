<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Policy-compliant UUID7 primary keys, matching the afwd `HasUuidPrimaryKey`
 * convention.
 *
 * The migration's `default(DB::raw('uuidv7()'))` keeps the database as the source
 * of truth (raw SQL inserts and seeders need no PHP help). This concern additionally
 * pre-fetches a `uuidv7()` value from Postgres before each insert so the model has
 * its id available immediately. PHP never generates the value — `Str::uuid7()` /
 * Ramsey UUID are deliberately NOT used, per the afwd UUID7 policy.
 */
trait HasUuid7PrimaryKey
{
    public static function bootHasUuid7PrimaryKey(): void
    {
        static::creating(function (Model $model): void {
            $key = $model->getKeyName();

            if (empty($model->{$key})) {
                /** @var string $uuid */
                $uuid = DB::scalar('SELECT uuidv7()::text');
                $model->{$key} = $uuid;
            }
        });
    }

    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }
}
