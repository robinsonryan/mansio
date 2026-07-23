<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use RobinsonRyan\Mansio\Contracts\ShareViewRenderer;
use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Tests\Fixtures\TestShareable;
use Symfony\Component\HttpFoundation\Response;

/**
 * The recipient-facing HTML surfaces render through the swappable
 * {@see ShareViewRenderer} seam, so a consuming app (Inertia, Livewire, a
 * themed Blade) can replace presentation without forking the controller's
 * resolve / guard / audit logic.
 */
final class FakeShareViewRenderer implements ShareViewRenderer
{
    /** @var array<int, array{surface: string, data: array<string, mixed>}> */
    public array $calls = [];

    public function show(Request $request, array $data): Response
    {
        $this->calls[] = ['surface' => 'show', 'data' => $data];

        return new Response('FAKE-SHOW:' . ($data['title'] ?? ''));
    }

    public function unlock(Request $request, array $data): Response
    {
        $this->calls[] = ['surface' => 'unlock', 'data' => $data];

        return new Response('FAKE-UNLOCK:' . ($data['challengeType'] ?? ''));
    }
}

it('renders the landing page through the bound ShareViewRenderer', function (): void {
    $fake = new FakeShareViewRenderer;
    $this->app->instance(ShareViewRenderer::class, $fake);

    $doc = TestShareable::create(['title' => 'Rendered doc']);
    Mansio::for($doc)->publishVersion('v1', ['summary' => 'Initial']);
    $share = Mansio::for($doc)->share();

    $this->get('docs/' . $share->token)
        ->assertOk()
        ->assertSee('FAKE-SHOW:Rendered doc');

    expect($fake->calls)->toHaveCount(1)
        ->and($fake->calls[0]['surface'])->toBe('show')
        ->and($fake->calls[0]['data'])->toHaveKeys(['title', 'currentVersion', 'downloadUrl', 'changelog']);
})->group('http');

it('renders the unlock challenge through the bound ShareViewRenderer', function (): void {
    $fake = new FakeShareViewRenderer;
    $this->app->instance(ShareViewRenderer::class, $fake);

    $doc = TestShareable::create(['title' => 'Locked doc']);
    Mansio::for($doc)->publishVersion('v1');
    $share = Mansio::for($doc)->share(['password' => 'letmein']);

    $this->get('docs/' . $share->token)
        ->assertOk()
        ->assertSee('FAKE-UNLOCK:password');

    expect($fake->calls)->toHaveCount(1)
        ->and($fake->calls[0]['surface'])->toBe('unlock')
        ->and($fake->calls[0]['data'])->toMatchArray(['challengeType' => 'password']);
})->group('http');
