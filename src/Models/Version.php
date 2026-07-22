<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RobinsonRyan\Mansio\Concerns\HasUuid7PrimaryKey;
use RobinsonRyan\Mansio\Contracts\Shareable;

/**
 * An immutable content snapshot owned by a shareable. Versions belong to the
 * shareable (not a share), so every link to it shares one history.
 *
 * @property string $id
 * @property string $shareable_type
 * @property string $shareable_id
 * @property int $sequence
 * @property string $content_path
 * @property string $mime
 * @property int $size_bytes
 * @property string $checksum
 * @property string|null $source_ref
 * @property string|null $summary
 * @property string|null $published_by
 * @property string|null $published_by_type
 * @property Carbon $published_at
 * @property Carbon|null $created_at
 * @property-read Model|null $shareable
 * @property-read bool $is_current
 */
class Version extends Model
{
    use HasUuid7PrimaryKey;

    /**
     * Versions are create-only snapshots; there is no updated_at column.
     */
    const UPDATED_AT = null;

    protected $table = 'mansio_versions';

    protected $guarded = [];

    protected $casts = [
        'sequence' => 'int',
        'size_bytes' => 'int',
        'published_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Constrain to the versions of a given shareable, newest sequence first.
     *
     * @param  Builder<Version>  $query
     */
    public function scopeLatestForShareable(Builder $query, Shareable $shareable): void
    {
        $query
            ->where('shareable_type', $shareable->getMorphClass())
            ->where('shareable_id', $shareable->getKey())
            ->orderByDesc('sequence');
    }

    /**
     * True when this is the highest-sequence version for its shareable.
     */
    public function getIsCurrentAttribute(): bool
    {
        $maxSequence = static::query()
            ->where('shareable_type', $this->shareable_type)
            ->where('shareable_id', $this->shareable_id)
            ->max('sequence');

        return $this->sequence === $maxSequence;
    }
}
