<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Actions;

use RobinsonRyan\Mansio\Events\ShareAccessed;
use RobinsonRyan\Mansio\Events\ShareDownloaded;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Models\ShareEvent;

/**
 * Persists a {@see ShareEvent} for an access (view / download / unlock attempt),
 * captures the IP + user agent, bumps the view counter on a view and dispatches the
 * matching domain event so consuming apps can hook access.
 */
final class RecordAccess
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function handle(Share $share, string $type, array $meta = []): ShareEvent
    {
        if ($type === ShareEvent::VIEWED) {
            $share->increment('view_count');
        }

        $attributes = [
            'type' => $type,
            'ip' => $meta['ip'] ?? null,
            'user_agent' => $meta['user_agent'] ?? null,
            'version_id' => $meta['version_id'] ?? null,
        ];

        $extra = $meta;
        unset($extra['ip'], $extra['user_agent'], $extra['version_id']);

        if ($extra !== []) {
            $attributes['meta'] = $extra;
        }

        $event = $share->recordEvent($type, $attributes);

        if ($type === ShareEvent::VIEWED) {
            event(new ShareAccessed($share, $event));
        } elseif ($type === ShareEvent::DOWNLOADED) {
            event(new ShareDownloaded($share, $event));
        }

        return $event;
    }
}
