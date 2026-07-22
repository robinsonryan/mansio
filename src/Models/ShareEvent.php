<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use RobinsonRyan\Mansio\Concerns\HasUuid7PrimaryKey;

/**
 * An audit record for a share: viewed / downloaded / unlock / revoked / expired,
 * with IP, user agent, and timestamp. Create-only.
 *
 * @property string $id
 * @property string $share_id
 * @property string|null $version_id
 * @property string $type
 * @property string|null $ip
 * @property string|null $user_agent
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property-read Share $share
 * @property-read Version|null $version
 */
class ShareEvent extends Model
{
    use HasUuid7PrimaryKey;

    /**
     * Audit rows are create-only; there is no updated_at column.
     */
    const UPDATED_AT = null;

    public const VIEWED = 'viewed';

    public const DOWNLOADED = 'downloaded';

    public const UNLOCK_ATTEMPT = 'unlock_attempt';

    public const UNLOCK_SUCCESS = 'unlock_success';

    public const REVOKED = 'revoked';

    public const EXPIRED = 'expired';

    protected $table = 'mansio_share_events';

    protected $guarded = [];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<Share, $this>
     */
    public function share(): BelongsTo
    {
        return $this->belongsTo(Share::class);
    }

    /**
     * @return BelongsTo<Version, $this>
     */
    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }
}
