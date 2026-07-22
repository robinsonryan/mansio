<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Contracts;

use RobinsonRyan\Mansio\Models\Version;

/**
 * Optional inline preview generation (thumbnail / first page). Apps may bind their
 * own implementation; the package ships none by default.
 */
interface PreviewGenerator
{
    /**
     * Return a previewable path (within the ContentStore) or null when unavailable.
     */
    public function preview(Version $version): ?string;
}
