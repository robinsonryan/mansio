# Mansio

**Secure, versioned, trackable delivery of any shareable resource via public links — for Laravel.**

Put a deliverable — a PDF proposal, an HTML quote, an image, a zip — in front of
external people who are *not* app users, behind a **stable public link** that
never changes when you update the content, shows recipients *what changed*,
expires/locks/revokes on demand, and records who viewed it.

> **The name.** In the Roman *cursus publicus*, a *mansio* was the official
> waystation on a road where couriers changed horses and a dispatch paused before
> being relayed onward — the node where a deliverable rests, versions, and is
> handed to the next stage. That is exactly what this package is: the waystation
> your artifacts pass through on the way to a recipient.

Google Drive gives you a stable link with file-swap but no changelog and no API
you control. Nextcloud gives password links and expiry but is a whole platform.
Mansio is the curated intersection, built as a decoupled domain you own.

---

## Features

- **Stable link + shareable-owned version history.** Versions belong to the
  *shareable*, not the link — publish a new version and every outstanding link
  serves it with no link change. Pin a link to a specific version when you don't
  want that.
- **Auto-changelog.** The landing page renders "what changed" from the version
  history (sequence, summary, published-at) — generated, not hand-typed.
- **Composable guard pipeline.** Every access rule is a small strategy:
  not-revoked, not-expired, view-limit, one-time (burn-after-reading),
  throttled password, email-OTP ("verify it's you"), IP allow-list. Ordered,
  config-driven, per-share overridable, and extensible with your own guards.
- **Medium-agnostic.** A "shareable" is anything that can yield bytes plus a mime
  type. The package has no proposal/quote/invoice knowledge.
- **Swappable content store.** Artifact bytes go through a `ContentStore`
  interface (Flysystem-backed default). S3, local, NAS — all fair game.
- **Full access audit.** Every hit records type, IP, user agent, and timestamp;
  view counts increment correctly.
- **Domain events.** `VersionPublished`, `ShareCreated`, `ShareAccessed`,
  `ShareDownloaded`, `ShareUnlockFailed`, `ShareRevoked`, `ShareExpired` — hook
  notifications or an audit mirror in your app without the package knowing they
  exist.
- **UUID7 primary keys** via Postgres-native `uuidv7()` (no PHP-side generation).
- **404, never 403.** Unknown, revoked, and expired tokens all 404 — the package
  never confirms a link ever existed.

## Requirements

- Laravel 13
- PHP 8.4+
- Postgres 18 (native `uuidv7()` backs the primary keys)

## Installation

Mansio is currently developed as an **in-repo path package** inside afwd. The host
app's `composer.json` declares a path repository:

```json
"repositories": [
    { "type": "path", "url": "packages/robinsonryan/mansio" }
],
```

```bash
composer require robinsonryan/mansio:@dev
```

The service provider is auto-discovered. Publish the config and migrations:

```bash
php artisan vendor:publish --tag=mansio-config
php artisan vendor:publish --tag=mansio-migrations
php artisan migrate
```

The recipient landing/unlock views ship as package views (`mansio::show`,
`mansio::unlock`); publish them too if you want to theme them in the app:

```bash
php artisan vendor:publish --tag=mansio-views
```

> When Mansio is later extracted to its own repository, only the `repositories`
> entry flips from `path` to `vcs` — nothing else changes.

## Quick start

### 1. Make a model shareable

Implement the `Shareable` contract and pull in the `IsShareable` trait. Eloquent
supplies `getKey()`/`getMorphClass()`; the trait supplies the rest with
overridable defaults.

```php
use RobinsonRyan\Mansio\Contracts\Shareable;
use RobinsonRyan\Mansio\Concerns\IsShareable;

class Proposal extends Model implements Shareable
{
    use IsShareable;

    // Optional overrides:
    public function mansioTitle(): string
    {
        return $this->customer_name . ' — Gulf Stream 4×4';
    }

    public function mansioDefaultMime(): string
    {
        return 'application/pdf';
    }

    public function mansioOwner(): ?object
    {
        return $this->tenant; // null disables owner scoping
    }
}
```

### 2. Publish a version

```php
use RobinsonRyan\Mansio\Facades\Mansio;

$version = Mansio::for($proposal)->publishVersion($pdfBytes, [
    'mime'       => 'application/pdf',
    'source_ref' => $gitSha,                       // build id / commit
    'summary'    => 'Corrected §5.1 transport totals',
]);
```

`$pdfBytes` may be a string or a stream resource. Each publish takes the next
`sequence` in the shareable's history. Or straight off the model:
`$proposal->publishVersion($pdfBytes, [...])`.

### 3. Mint a share

```php
$share = Mansio::for($proposal)->share([
    'expires_at' => now()->addDays(30),
    'password'   => null,          // set a string to require a password
    'max_views'  => null,          // cap total views
    'one_time'   => false,         // burn after first successful view
    'label'      => 'Gulf Stream — Bob',
    'pinned_version_id' => null,    // null = always serve latest
    'settings'   => null,          // per-share guard overrides (OTP emails, IPs…)
]);

$share->url();   // https://your-app.test/docs/{token}
```

### 4. Serve, revoke, roll back

```php
$share = Mansio::resolve($token, $context);  // runs the guard pipeline; 404 on failure
Mansio::revoke($share);                       // subsequent resolution 404s
Mansio::rollback($proposal, toSequence: 3);   // republish sequence 3 as the new latest
```

In practice the HTTP layer does the resolving for you — the routes below wrap
`Mansio::resolve()`.

## Public routes

Registered under the configured prefix (`docs` by default), in a route group with
**no app auth guard** — the guard pipeline is the only gate.

| Method | Path | Name | Purpose |
| :-- | :-- | :-- | :-- |
| GET  | `/docs/{token}` | `mansio.show` | Landing: title, changelog, preview, download |
| GET  | `/docs/{token}/download` | `mansio.download` | Streamed artifact delivery |
| GET  | `/docs/{token}/preview/{seq?}` | `mansio.preview` | Inline preview |
| POST | `/docs/{token}/unlock` | `mansio.unlock` | Password / OTP challenge |

`ResolveShareMiddleware` binds `{token}` to a `Share`, runs the guard pipeline,
records the event, and returns 404 for unknown/revoked/expired tokens.

## Configuration

`config/mansio.php` (excerpt):

```php
'route'  => ['prefix' => 'docs', 'middleware' => ['web'], 'domain' => null],
'store'  => ['driver' => 'flysystem', 'disk' => env('MANSIO_DISK', 'local'), 'path' => 'mansio'],
'token'  => ['length' => 32],
'id_type'=> 'uuid7',                 // Postgres uuidv7() column default
'owner'  => ['enabled' => true],     // record an owner morph for tenant scoping
'guards' => [
    NotRevokedGuard::class,
    NotExpiredGuard::class,
    WithinViewLimitGuard::class,
    OneTimeGuard::class,
    PasswordGuard::class,
    // EmailOtpGuard / IpAllowGuard are opt-in per share via settings
],
'password' => ['throttle' => ['max_attempts' => 5, 'decay_minutes' => 10]],
'models'   => ['share' => Share::class, 'version' => Version::class, 'event' => ShareEvent::class],
```

- **`guards`** — the default ordered pipeline. Order matters; the first non-pass
  result short-circuits. Add your own guard classes here.
- **`store.disk`** — any Laravel filesystem disk; the default store writes under
  `store.path`.
- **`route.prefix` / `route.domain`** — where the public routes mount.
- **`owner.enabled`** — when on, shares record the shareable's `mansioOwner()`
  morph so you can scope management queries per tenant/account.
- **`models`** — swap in extended models; the package resolves these bindings.

## The guard pipeline

Each guard is an `AccessGuard` returning a `GuardResult`: **pass**, **deny(reason)**,
or **challenge(type)** (short-circuits to the unlock flow, e.g. password/OTP). The
pipeline runs guards in order on every hit; the first non-pass wins.

Built-ins: `NotRevokedGuard`, `NotExpiredGuard`, `WithinViewLimitGuard`,
`OneTimeGuard`, `PasswordGuard`, `EmailOtpGuard` (opt-in), `IpAllowGuard` (opt-in).

Add a custom guard:

```php
use RobinsonRyan\Mansio\Contracts\AccessGuard;
use RobinsonRyan\Mansio\Support\ShareContext;
use RobinsonRyan\Mansio\Support\GuardResult;

final class RequireSsoGuard implements AccessGuard
{
    public function check(ShareContext $context): GuardResult
    {
        return $context->credential('sso_verified')
            ? GuardResult::pass()
            : GuardResult::challenge('sso');
    }
}
```

Register it in `config('mansio.guards')`. `ShareContext` carries the resolved
`share`, the request `ip` / `userAgent`, and submitted `credentials`.

## Events

Emitted for app-side listeners (notifications, audit mirrors, webhooks):

| Event | Fired when |
| :-- | :-- |
| `VersionPublished` | A new content version is published |
| `ShareCreated` | A share link is minted |
| `ShareAccessed` | A share landing page is viewed |
| `ShareDownloaded` | An artifact is downloaded |
| `ShareUnlockFailed` | A password/OTP unlock attempt fails |
| `ShareRevoked` | A share is revoked |
| `ShareExpired` | A share is hit past its expiry |

## Concepts

| Concept | What it is |
| :-- | :-- |
| **Shareable** | Any consumer model implementing `Shareable`. Yields versions of content, stored as a morph. |
| **Version** | An immutable content snapshot for a shareable (bytes + mime, size, checksum, source ref, summary, sequence). Owned by the shareable, so all its links share one history. |
| **Share** | A public link (random token) pointing at a shareable. Serves the current version, or a pinned one. Carries its own access settings — many shares per shareable. |
| **AccessGuard** | A single access rule evaluated on every hit. Ordered, config-driven pipeline. |
| **ContentStore** | Storage backend abstraction for artifact bytes. |
| **ShareViewRenderer** | Renders the recipient landing page + unlock challenge. Default ships Blade; rebind for Inertia / Livewire / a themed surface without touching access control. |
| **ShareEvent** | Audit record: viewed / downloaded / unlock / revoked / expired, with IP + UA + timestamp. |

## Testing

The suite runs on **Pest 4 via Orchestra Testbench**, against **Postgres** (not
sqlite) because the UUID7 primary keys depend on Postgres 18's native `uuidv7()`.

```bash
composer test        # pest
composer quality     # pint --test, phpstan, pest
```

Inside afwd, run against the DDEV Postgres container:

```bash
ddev exec bash -c 'cd packages/robinsonryan/mansio && ./vendor/bin/pest'
```

The whole suite passing in package isolation (no afwd autoload) is the proof of
decoupling; `tests/Unit/ArchitectureTest.php` additionally asserts no
`RobinsonRyan\Mansio` class references an `App\` class.

## License

MIT. See [LICENSE.md](LICENSE.md).
