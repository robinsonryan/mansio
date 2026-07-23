<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Http;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use RobinsonRyan\Mansio\Contracts\ShareViewRenderer;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default {@see ShareViewRenderer}: renders the package's own Blade views
 * (`mansio::show` / `mansio::unlock`), which apps may override via the standard
 * `resources/views/vendor/mansio` publish path.
 */
final class BladeShareViewRenderer implements ShareViewRenderer
{
    public function __construct(private readonly ResponseFactory $responses) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function show(Request $request, array $data): Response
    {
        return $this->responses->view('mansio::show', $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function unlock(Request $request, array $data): Response
    {
        return $this->responses->view('mansio::unlock', $data);
    }
}
