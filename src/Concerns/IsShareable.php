<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use RobinsonRyan\Mansio\Contracts\Shareable;
use RobinsonRyan\Mansio\Facades\Mansio;
use RobinsonRyan\Mansio\Models\Share;
use RobinsonRyan\Mansio\Models\Version;

/**
 * Convenience implementation of the {@see Shareable} contract for Eloquent models.
 * Add `implements Shareable` and `use IsShareable` to any model to make it shareable;
 * `getKey()` / `getMorphClass()` come from Eloquent, the rest is provided here with
 * sensible, overridable defaults.
 *
 * @mixin Model
 *
 * @phpstan-require-implements Shareable
 */
trait IsShareable
{
    /**
     * The shareable's version history, newest sequence first.
     *
     * @return MorphMany<Version, $this>
     */
    public function mansioVersions(): MorphMany
    {
        /** @var class-string<Version> $versionClass */
        $versionClass = config('mansio.models.version', Version::class);

        return $this->morphMany($versionClass, 'shareable')->orderByDesc('sequence');
    }

    /**
     * The public links pointing at this shareable.
     *
     * @return MorphMany<Share, $this>
     */
    public function mansioShares(): MorphMany
    {
        /** @var class-string<Share> $shareClass */
        $shareClass = config('mansio.models.share', Share::class);

        return $this->morphMany($shareClass, 'shareable');
    }

    /**
     * Publish a new immutable content version for this shareable.
     *
     * @param  string|resource  $bytes
     * @param  array<string, mixed>  $meta
     */
    public function publishVersion(mixed $bytes, array $meta = []): Version
    {
        /** @var Shareable $this */
        return Mansio::for($this)->publishVersion($bytes, $meta);
    }

    /**
     * Mint a public share link for this shareable.
     *
     * @param  array<string, mixed>  $options
     */
    public function share(array $options = []): Share
    {
        /** @var Shareable $this */
        return Mansio::for($this)->share($options);
    }

    /**
     * Recipient-facing title. Defaults to the class basename plus key.
     */
    public function mansioTitle(): string
    {
        return Str::headline(class_basename($this)) . ' ' . $this->getKey();
    }

    /**
     * Optional owner morph for tenant scoping. Null disables scoping.
     */
    public function mansioOwner(): ?object
    {
        return null;
    }

    /**
     * Default mime for versions published without an explicit one.
     */
    public function mansioDefaultMime(): string
    {
        return 'application/pdf';
    }
}
