<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RobinsonRyan\Mansio\Http\Controllers\ShareController;
use RobinsonRyan\Mansio\Http\Middleware\ResolveShareMiddleware;

/*
|--------------------------------------------------------------------------
| Mansio public share routes
|--------------------------------------------------------------------------
| Mounted by MansioServiceProvider inside the configured route group
| (prefix / middleware / domain). The app auth guard is intentionally absent —
| the guard pipeline is the only gate. Every route binds an active share via
| ResolveShareMiddleware (404 for unknown/revoked/expired, never a leak).
*/

Route::middleware(ResolveShareMiddleware::class)->group(function (): void {
    Route::get('/{token}', [ShareController::class, 'show'])->name('mansio.show');
    Route::get('/{token}/download', [ShareController::class, 'download'])->name('mansio.download');
    Route::get('/{token}/preview/{seq?}', [ShareController::class, 'preview'])->name('mansio.preview');
    Route::post('/{token}/unlock', [ShareController::class, 'unlock'])->name('mansio.unlock');
});
