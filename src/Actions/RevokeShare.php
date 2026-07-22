<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Actions;

use RobinsonRyan\Mansio\Events\ShareRevoked;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Models\ShareEvent;

/**
 * Revokes a share: flips its status, stamps the revocation time, writes an audit
 * event and dispatches {@see ShareRevoked}. Subsequent token resolution 404s.
 */
final class RevokeShare
{
    public function handle(Share $share): Share
    {
        $share->forceFill([
            'status' => 'revoked',
            'revoked_at' => now(),
        ])->save();

        $share->recordEvent(ShareEvent::REVOKED);

        event(new ShareRevoked($share));

        return $share;
    }
}
