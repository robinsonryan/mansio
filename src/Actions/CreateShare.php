<?php

declare(strict_types=1);

namespace RobinsonRyan\Mansio\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use RobinsonRyan\Mansio\Contracts\Shareable;
use RobinsonRyan\Mansio\Contracts\TokenGenerator;
use RobinsonRyan\Mansio\Events\ShareCreated;
use RobinsonRyan\Mansio\Models\Share;

/**
 * Mints a public share link pointing at a shareable: generates an unguessable token
 * (retrying on the rare unique collision), applies the requested access options
 * (expiry, password, view limits, pinning, label, settings) and records the optional
 * owner morph for tenant scoping.
 */
final class CreateShare
{
    public function __construct(private readonly TokenGenerator $tokens) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function handle(Shareable $shareable, array $options = []): Share
    {
        /** @var class-string<Share> $shareClass */
        $shareClass = config('mansio.models.share', Share::class);

        $attributes = [
            'shareable_type' => $shareable->getMorphClass(),
            'shareable_id' => $shareable->getKey(),
            'token' => $this->uniqueToken($shareClass),
            'status' => 'active',
            'pinned_version_id' => $options['pinned_version_id'] ?? null,
            'label' => $options['label'] ?? null,
            'expires_at' => $options['expires_at'] ?? null,
            'password_hash' => $this->passwordHash($options),
            'max_views' => $options['max_views'] ?? null,
            'view_count' => 0,
            'one_time' => (bool) ($options['one_time'] ?? false),
            'settings' => $options['settings'] ?? null,
        ];

        if (config('mansio.owner.enabled')) {
            $owner = $shareable->mansioOwner();

            if ($owner instanceof Model) {
                $attributes['owner_type'] = $owner->getMorphClass();
                $attributes['owner_id'] = $owner->getKey();
            }
        }

        /** @var Share $share */
        $share = $shareClass::query()->create($attributes);

        event(new ShareCreated($share));

        return $share;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    private function passwordHash(array $options): ?string
    {
        $password = $options['password'] ?? null;

        return $password === null || $password === ''
            ? null
            : Hash::make((string) $password);
    }

    /**
     * @param  class-string<Share>  $shareClass
     */
    private function uniqueToken(string $shareClass): string
    {
        do {
            $token = $this->tokens->generate();
        } while ($shareClass::query()->where('token', $token)->exists());

        return $token;
    }
}
