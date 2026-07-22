<?php

declare(strict_types=1);

use RobinsonRyan\Mansio\Contracts\Shareable;
use Symfony\Component\Finder\Finder;

/**
 * R21 / R1 — the package is extractable and app-agnostic: no `RobinsonRyan\Mansio`
 * class may reference an `App\` class. The whole suite passing in package isolation
 * (testbench, no afwd autoload) is the broader proof; this guards the coupling line.
 */
it('never references an App class from package source', function (): void {
    $srcDir = dirname(__DIR__, 2) . '/src';

    $offenders = [];

    foreach (Finder::create()->files()->in($srcDir)->name('*.php') as $file) {
        $contents = (string) file_get_contents($file->getRealPath());

        // Match a real `App\` namespace reference (use statement or FQCN),
        // not substrings like "Application" or "$app".
        if (preg_match('/(?:^|[^A-Za-z0-9_\\\\])App\\\\/m', $contents) === 1) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});

it('exposes the Shareable contract without depending on any concrete consumer', function (): void {
    $contract = dirname(__DIR__, 2) . '/src/Contracts/Shareable.php';

    expect(file_exists($contract))->toBeTrue()
        ->and(interface_exists(Shareable::class))->toBeTrue();
});
