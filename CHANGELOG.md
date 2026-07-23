# Changelog

All notable changes to `robinsonryan/mansio` are documented here.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.1]

### Added

- **`ShareViewRenderer` seam.** The recipient-facing HTML surfaces (landing page
  and unlock challenge) now render through a bound `ShareViewRenderer` contract
  instead of calling `view()` directly. The default `BladeShareViewRenderer`
  preserves the shipped Blade views (and the `vendor/mansio` publish override).
  Consuming apps may bind their own implementation (Inertia, Livewire, a themed
  Blade) without forking the controller's resolve / guard / audit logic — the
  controller stays the single source of truth for access control.

### Changed

- **`HasUuid7PrimaryKey` now hydrates the key via `INSERT ... RETURNING`** in a
  single roundtrip (a `performInsert()` override) rather than pre-fetching
  `SELECT uuidv7()` before each insert. Postgres still mints the UUID from the
  column default; PHP never generates it. Behaviour is unchanged for callers
  (an explicit id still uses the standard insert path).

## [0.1.0]

Initial release.

### Added

- **Shareable contract + `IsShareable` trait.** Any Eloquent model becomes
  shareable by implementing `Shareable` and using `IsShareable`, with zero
  coupling from the package to the consuming app.
- **Immutable content versions.** `Mansio::for($model)->publishVersion($bytes, $meta)`
  writes bytes to the content store and records a versioned, sequenced snapshot
  (mime, size, sha256 checksum, source ref, summary, publisher).
- **Public share links.** `->share($options)` mints an unguessable base62 token
  pointing at the shareable, with expiry, password, view-limit, one-time, label,
  pinning, and per-share settings.
- **Stable link + shareable-owned version history.** A link always serves the
  shareable's current (latest-sequence) version, so publishing a new version
  updates every outstanding link with no link change.
- **Version pinning.** A share may pin to a specific version instead of tracking
  latest.
- **Rollback.** `Mansio::rollback($model, toSequence: n)` republishes an earlier
  version as the new latest.
- **Composable guard pipeline.** Ordered, config-driven `AccessGuard` strategies,
  per-share overridable: `NotRevokedGuard`, `NotExpiredGuard`,
  `WithinViewLimitGuard`, `OneTimeGuard` (burn-after-reading), `PasswordGuard`
  (hashed, throttled attempts), `EmailOtpGuard` (opt-in "verify identity" tier),
  `IpAllowGuard` (opt-in allow-list). Apps register their own via config.
- **Revocation.** `Mansio::revoke($share)`; revoked links resolve to 404.
- **Access audit trail.** Every hit records a `ShareEvent`
  (viewed / downloaded / unlock attempt / unlock success / revoked / expired)
  with IP, user agent, timestamp, and meta; view counts increment correctly.
- **Auto-changelog.** The recipient landing page renders "what changed" from the
  version history (sequence, summary, published-at).
- **Streamed delivery.** Download route streams artifact bytes from the
  `ContentStore` (X-Sendfile / X-Accel friendly).
- **Swappable content store.** `ContentStore` contract with a Flysystem-backed
  default over any Laravel filesystem disk.
- **Optional owner scoping.** Shares record the shareable's `mansioOwner()` morph
  (tenant/account) for scoped management queries; toggled via config.
- **Domain events** for app-side listeners: `VersionPublished`, `ShareCreated`,
  `ShareAccessed`, `ShareDownloaded`, `ShareUnlockFailed`, `ShareRevoked`,
  `ShareExpired`.
- **404, never 403.** Unknown, revoked, and expired tokens all return 404 via
  `ResolveShareMiddleware`, never confirming a link existed.
- **Policy-compliant UUID7 primary keys** via a `HasUuid7PrimaryKey` concern
  backed by Postgres 18's native `uuidv7()` column default — no PHP-side UUID
  generation.
- **Extractable package skeleton:** own `composer.json`, service provider,
  namespace (`RobinsonRyan\Mansio`), publishable config/migrations/views, and
  quality scripts (`composer test` / `lint` / `analyze` / `quality`).

[Unreleased]: https://github.com/robinsonryan/mansio/commits/main
