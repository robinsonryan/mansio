<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Contracts;

use Illuminate\Http\Request;
use RobinsonRyan\Mansio\Http\BladeShareViewRenderer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renders the two recipient-facing HTML surfaces of a share — the landing page
 * and the unlock challenge — into an HTTP response.
 *
 * The package ships a Blade implementation ({@see BladeShareViewRenderer}),
 * bound by default in the service provider. Consuming apps may bind their own
 * implementation (Inertia, Livewire, a custom theme) without forking the
 * controller's resolve / guard / audit logic — the controller stays the single
 * source of truth for access control and only delegates presentation here.
 *
 * Data arrays mirror what the controller passes to the default Blade views:
 *  - show:   ['title' => string, 'currentVersion' => Version|null,
 *             'downloadUrl' => string, 'changelog' => array<int, array{
 *                 sequence:int, summary:string|null, published_at:mixed}>]
 *  - unlock: ['token' => string, 'challengeType' => 'password'|'otp',
 *             'error' => string|null]
 */
interface ShareViewRenderer
{
    /**
     * Render the share landing page.
     *
     * @param  array<string, mixed>  $data
     */
    public function show(Request $request, array $data): Response;

    /**
     * Render the unlock challenge (password / OTP).
     *
     * @param  array<string, mixed>  $data
     */
    public function unlock(Request $request, array $data): Response;
}
