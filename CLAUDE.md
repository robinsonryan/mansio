# Mansio — Package Notes

AI-agent guidance for working **inside** the `robinsonryan/mansio` package.
This overrides generic assumptions; read it before editing.

## What this is

Secure, versioned, trackable delivery of any shareable resource via public links.
Namespace `RobinsonRyan\Mansio`. Laravel 13, PHP 8.4+, Postgres 18. Developed as
an in-repo **path package** under `packages/robinsonryan/mansio/`, slated for
extraction to its own repo (composer `repositories` flips `path` → `vcs`; nothing
else changes).

## Decoupling invariant (hard rule)

**No `RobinsonRyan\Mansio` class may reference an `App\` class — ever.** Consumers
become shareable by implementing the `Shareable` contract, never the reverse. This
is mechanically enforced by `tests/Unit/ArchitectureTest.php` (greps `src/` for any
`App\` reference) and by the suite passing in testbench isolation with no afwd
autoload. Keep the package medium-agnostic: it sees finished bytes + a mime type,
never proposal/quote/invoice semantics. No markdown→PDF rendering (that's app/CI).

## Architecture — interface-first

- `Contracts/` — the seams: `Shareable`, `AccessGuard`, `ContentStore`,
  `TokenGenerator`, `PreviewGenerator`. Consumers and swappable backends bind to
  these.
- `Concerns/IsShareable` — convenience implementation of `Shareable` for Eloquent
  models (`mansioVersions`/`mansioShares` relations, `publishVersion`/`share`
  helpers, overridable `mansioTitle`/`mansioOwner`/`mansioDefaultMime`).
- Default bindings live in `MansioServiceProvider::register()`
  (`TokenGenerator` → `Base62TokenGenerator`, `ContentStore` →
  `FlysystemContentStore`, `Mansio` singleton aliased `mansio`).

## Manager + facade seam

`Mansio` (`src/Mansio.php`) is **the contract**; HTTP is a thin wrapper over it.
Every operation delegates to a container-resolved action in `src/Actions/`
(`PublishVersion`, `CreateShare`, `ResolveShare`, `RevokeShare`,
`RollbackVersion`, `RecordAccess`) so behavior stays swappable and unit-testable.
Public API:

```php
Mansio::for($model)->publishVersion($bytes, $meta);  // → Version
Mansio::for($model)->share($options);                // → Share
Mansio::resolve($token, $context);                   // → Share (runs guard pipeline)
Mansio::revoke($share);                              // → Share
Mansio::rollback($model, toSequence: $n);            // → Version
```

Routes (`routes/mansio.php`) are `mansio.show|download|preview|unlock`, mounted by
the provider under `config('mansio.route')` with **no app auth guard** —
`ResolveShareMiddleware` + the guard pipeline are the only gate. Bad tokens 404,
never 403 (never confirm a link existed).

## Guard pipeline (extension point)

Access rules are `AccessGuard` strategies returning a `GuardResult`
(`pass` / `deny(reason)` / `challenge(type)`), run in order by `GuardPipeline`;
first non-pass short-circuits. Built-ins in `src/Guards/`. Order and set come from
`config('mansio.guards')`; `EmailOtpGuard`/`IpAllowGuard` are opt-in per share via
`settings`. Add a rule = new small class implementing `AccessGuard`, registered in
config — never widen a boolean column list.

## UUID7 policy (own concern)

Use `Concerns/HasUuid7PrimaryKey` on all package models. It sets
`$incrementing = false; $keyType = 'string';` and pre-fetches a value via
`DB::scalar('SELECT uuidv7()::text')` in a `creating` hook, with the migration's
`default(DB::raw('uuidv7()'))` as the DB source of truth. **Never** generate UUIDs
PHP-side (`Str::uuid7()` / Ramsey) and **never** reuse the older afwd `HasUuid7` /
stock `HasUuids` patterns the afwd CLAUDE.md flags for removal.

## Testing (Postgres, not sqlite)

Pest 4 + Orchestra Testbench, run against **Postgres** — the UUID7 keys need
Postgres 18's native `uuidv7()`, so sqlite won't do. From afwd, run inside DDEV:

```bash
ddev exec bash -c 'cd packages/robinsonryan/mansio && ./vendor/bin/pest'
```

Feature tests map to the requirements ledger (R1–R21) in
`plans/packages/mansio.md`; fixtures live in `tests/Fixtures/`
(`TestShareable`, `OwnedShareable`, `TestOwner`).

## Quality commands

```bash
composer test        # ./vendor/bin/pest
composer lint        # pint (write)   |  composer lint:check   pint --test
composer analyze     # phpstan (larastan)
composer refactor:check   # rector --dry-run
composer quality     # lint:check + analyze + test
```

Own the whole gate: every failing check is yours before "done", regardless of who
introduced it. Do NOT modify `vite.config.ts`-style locked files — here that means
don't touch `composer.json`, migrations, or config keys casually; they're the
extraction contract.
