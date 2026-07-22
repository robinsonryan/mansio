<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;
use RobinsonRyan\Mansio\Concerns\HasUuid7PrimaryKey;
use RobinsonRyan\Mansio\Contracts\Shareable;

/**
 * A public link pointing at a shareable. Serves the shareable's current version
 * (or a pinned one) and carries its own access settings.
 *
 * @property string $id
 * @property string $shareable_type
 * @property string $shareable_id
 * @property string|null $owner_type
 * @property string|null $owner_id
 * @property string $token
 * @property string|null $pinned_version_id
 * @property string|null $label
 * @property string $status
 * @property Carbon|null $expires_at
 * @property string|null $password_hash
 * @property int|null $max_views
 * @property int $view_count
 * @property bool $one_time
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Model|null $shareable
 * @property-read Model|null $owner
 * @property-read Version|null $pinnedVersion
 * @property-read Collection<int, ShareEvent> $events
 */
class Share extends Model
{
    use HasUuid7PrimaryKey;

    protected $table = 'mansio_shares';

    protected $guarded = [];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'settings' => 'array',
        'max_views' => 'int',
        'view_count' => 'int',
        'one_time' => 'bool',
    ];

    /**
     * @return MorphTo<Model, $this>
     */
    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<Version, $this>
     */
    public function pinnedVersion(): BelongsTo
    {
        return $this->belongsTo(Version::class, 'pinned_version_id');
    }

    /**
     * @return HasMany<ShareEvent, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(ShareEvent::class);
    }

    /**
     * The shareable's latest-sequence version.
     */
    public function currentVersion(): ?Version
    {
        return Version::query()
            ->where('shareable_type', $this->shareable_type)
            ->where('shareable_id', $this->shareable_id)
            ->orderByDesc('sequence')
            ->first();
    }

    /**
     * The version this link serves: the pinned one when set, otherwise the current.
     */
    public function serveableVersion(): ?Version
    {
        if ($this->pinned_version_id !== null) {
            return $this->pinnedVersion;
        }

        return $this->currentVersion();
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked' || $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Persist an audit event for this share.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function recordEvent(string $type, array $attributes = []): ShareEvent
    {
        return ShareEvent::create(array_merge($attributes, [
            'share_id' => $this->getKey(),
            'type' => $type,
        ]));
    }

    /**
     * The public URL for this share's landing page.
     */
    public function url(): string
    {
        return route('mansio.show', $this->token);
    }

    /**
     * @param  Builder<Share>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('status', 'active');
    }

    /**
     * @param  Builder<Share>  $query
     */
    public function scopeForShareable(Builder $query, Shareable $shareable): void
    {
        $query
            ->where('shareable_type', $shareable->getMorphClass())
            ->where('shareable_id', $shareable->getKey());
    }
}
