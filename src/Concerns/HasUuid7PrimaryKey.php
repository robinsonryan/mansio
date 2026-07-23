<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Policy-compliant UUID7 primary keys, matching the afwd `HasUuidPrimaryKey`
 * convention.
 *
 * The migration's `default(DB::raw('uuidv7()'))` keeps the database as the source
 * of truth for UUIDs (raw SQL inserts and seeders need no PHP help). On insert we
 * let that default fire and read the generated key back via `INSERT ... RETURNING`
 * in a single roundtrip — the same mechanism Eloquent uses for auto-increment
 * keys, so the created model has its id immediately. PHP never generates the
 * value (`Str::uuid7()` / Ramsey UUID are deliberately NOT used, per the afwd
 * UUID7 policy).
 *
 * There is no native Laravel or package equivalent: Laravel's HasUuids and every
 * community package generate UUIDs PHP-side (see laravel/framework#27989).
 */
trait HasUuid7PrimaryKey
{
    public function getIncrementing(): bool
    {
        return false;
    }

    public function getKeyType(): string
    {
        return 'string';
    }

    /**
     * Insert the model, letting Postgres' column default mint the UUID and
     * hydrating the key from the INSERT's RETURNING clause in one roundtrip.
     */
    protected function performInsert(Builder $query): bool
    {
        $key = $this->getKeyName();

        // An explicit id (replication, imports, tests) uses the standard path.
        if (! empty($this->getAttribute($key))) {
            return parent::performInsert($query);
        }

        if ($this->usesUniqueIds()) {
            $this->setUniqueIds();
        }

        if ($this->fireModelEvent('creating') === false) {
            return false;
        }

        if ($this->usesTimestamps()) {
            $this->updateTimestamps();
        }

        $attributes = $this->getAttributesForInsert();
        unset($attributes[$key]); // omit id so the uuidv7() DEFAULT fires

        // insertGetId compiles to `INSERT ... RETURNING <key>` on Postgres.
        $id = $query->getQuery()->insertGetId($attributes, $key);
        $this->setAttribute($key, $id);

        $this->exists = true;
        $this->wasRecentlyCreated = true;
        $this->fireModelEvent('created', false);

        return true;
    }
}
