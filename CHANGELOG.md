# Changelog

## [0.17.1] - 2026-07-24

### Fixed

- **RelationLoader**: Fixed `is_callable()` false positive when eager loading relation names that match global helper functions. String values are now always treated as relation names, never as constraint callables.

### Changed

- **dompdf/dompdf**: Bumped from `^2.0` to `^3.0` in `require-dev` and `suggest` to resolve security advisories blocking CI.

## [0.17.0] - 2026-07-11

### Features

- **ApiResponseTrait**: New `Lightpack\Http\ApiResponseTrait` for consistent `{success, message, data, errors}` JSON API responses.

### Fixed

- **ExceptionRenderer**: Production JSON errors now expose `HttpException` 4xx messages instead of a generic "technical issues" message.
- **RecordNotFoundException**: Message corrected to "Record not found.".
- **Debug JSON**: Development error responses now include the `message` field.

## [0.16.3] - 2026-10-17

### Fixed

- **Dumper**: CLI `dd()` now dumps **all** arguments (was silently dropping everything after the first). Color is now properly reset after output.
- **Dumper**: `printDump()` is deprecated. Use `varDump()` instead.

## [0.16.2] - 2026-07-05

### Fixed

- **Env**: Boolean strings from OS environment variables (`'true'`, `'false'`) are now correctly converted to actual booleans.
- **DB**: Fixed `printQueryLogs()` crash when no queries were logged. Fixed `logQuery()` ignoring `APP_DEBUG` when it arrived as a string.
- **Dumper**: `dd()` now renders arrays and objects with `toArray()` in a collapsible tree instead of raw output.

## [0.16.1] - 2026-07-05

### Fixed

- **ExceptionRenderer**: Improved error handling and response formatting for both development and production environments.

## [0.16.0] - 2026-07-05

### Features

- **ResourceQuery**: New `Lightpack\Database\Lucid\ResourceQuery` class for declarative API list endpoints. Reads `?filter`, `?sort`, `?include`, `?count`, `?sum`, `?avg`, `?min`, `?max`, `?fields`, and `?page` parameters from the request and translates them into ORM operations behind an explicit allowlist.
- **Model::resourceQuery()**: Convenience shortcut returning `ResourceQuery::for(static::class)`.
- **hasManyThrough aggregates**: `withSum()`, `withAvg()`, `withMin()`, and `withMax()` now support `hasManyThrough` relations (previously `hasMany` only).

## [0.15.0] - 2026-07-02

### Features

- **Lang module**: Support for multiple locales.
- **`create:lang` command**: New interactive console command to scaffold language files.
- **Validation stub**: Make it easy to override validation messages for different locales.

## [0.14.0] - 2026-06-30

### Features

- **Tenancy-aware modules**: Audit, RBAC, SocialAuth, Tags, Taxonomies, and Uploads now support multi-tenant data isolation out of the box.
- **Tenant-scoped models**: Added `TenantPermission`, `TenantRole`, `TenantSocialAccountModel`, `TenantTag`, and `TenantTaxonomy` for per-tenant records.
- **Taxonomies**: Extracted `HierarchicalTrait` to share parent/child tree logic across taxonomy implementations.
- **ORM**: Enhanced `Model`, `RelationHandler`, and `TenantModel` for improved tenancy-aware queries and eager loading.
- **Migrations**: Updated console migration views for Audits, RBAC, SocialAuth, Tags, Taxonomies, and Uploads to include tenant columns.

## [0.13.0] - 2026-06-28

### Features

- **Form**: Added `Lightpack\Html\Form` class to support easy to produce sticky forms, CSRF tokens, spoofed methods, hidden fields, pre-rendered inputs, and show validation errors.

## [0.12.0] - 2026-06-27

### Features

- **Console**: Added `create:factory` command to generate factory classes in `database/factories`. Supports plain `Factory` and `ModelFactory` via `--model` flag.

## [0.11.0] - 2026-06-25

### Features

- **Deploy**: Added `db:drop` command to drop MySQL databases and/or users on remote servers.

## [0.10.0] - 2026-06-22

### Features

- **Deploy Suite**. Lightpack now ships with everything you need to go from a fresh Ubuntu server to a running application right from your local development machine. Provision the server once, then deploy, rollback, and manage your app from the console on your laptop.
- You get: one-command server setup (nginx, PHP-FPM, MySQL, Redis, firewall, SSL), deployments that pull code, sync secrets, run migrations, and reload services, instant rollbacks to any previous commit, remote database backups and restores, queue workers managed via Supervisor, cron scheduling, and the ability to run arbitrary commands on your server.

## [0.9.8] - 2026-05-26

### Fixes & Refactors

- **ValidationException**: Added `setResponse()` / `getResponse()` to carry a prepared `Response` through the exception boundary.
- **FormRequest / API Validation**: JSON validation failure path now builds a `Response` via the `response()` service (instead of `redirect()`) and carries it through `ValidationException`.
- **Dispatcher**: Returns the `Response` carried by `ValidationException` when present, falling back to `redirect()` for web contexts.

## [0.9.7] - 2026-05-26

### Fixes & Refactors

- **FormRequest / Container**: Fixed container self-resolution bug in `__boot()` by using `Container::getInstance()` instead of DI-injected `Container`, preventing "Service `redirect` is not registered" errors during API validation.
- **FormRequest / API Validation**: Fixed JSON validation failure path to throw `ValidationException` instead of returning, ensuring API endpoints correctly return `422 Unprocessable Entity`.
- **FormRequest**: Improved PHPDoc for `data()`, `beforeSend()`, and `beforeRedirect()` hooks.

## [0.9.6] - 2026-05-25

### Features

- **Process::spawn()**: Added support to spawn child processes. Streams output directly to the terminal (no buffering). Supports custom working directory, environment variables, and signal-safe termination.
- **WatchesEnvTrait**: Monitors `.env` file changes and gracefully restarts the child process. Used by `ServeCommand` and `ProcessJobs`.

## [0.9.5] - 2026-05-22

### Features

- **Route Model Binding**: Automatic dependency injection for route parameters. Group-level binding with inheritance and override support.
- **Moment**: Added methods for date comparison and boundary calculations and improved datetime handling.

## [0.9.4] - 2026-05-20

### Fixes & Refactors

- **Schema / Foreign Keys**: Auto-generated constraint names (`fk_{table}_{column}`) for predictable `dropForeign()` calls. Override via `->name('custom_name')`.
- **Query Builder**: `update()` and `delete()` now require an explicit `WHERE` clause for safeguard. Prevents accidental full-table mutations.

## [0.9.3] - 2026-05-20

### Features

- **Limited eager loading** for `hasMany` and `hasManyThrough` relations using `ROW_NUMBER()` window functions (`limit()` constraints in eager loads now apply per-parent).
- **Relation aggregates**: `withSum()`, `withAvg()`, `withMin()`, `withMax()`, `withCount()` with correlated subquery support for `orderBy()`.
- **Collection** additions: `last()`, `sort()`.
- **Arr utils**: `flatten()`, `groupBy()`, `sort()`.
- **ServeCommand**: port validation, availability checking, and ASCII art banner.
- **ProcessJobs**: ASCII art banner on startup.
- **Pagination**: `isEmpty()` and `isNotEmpty()` methods.

### Fixes & Refactors

- **HTTP**: Set `Content-Type` header when `setType()` is called.
- **Debug**: Use relevant trace file/line in exception renderer.
- **Tests**: Reset container instance after each test in `tearDown()`.
- **Query**: Make aggregate `groupBy()` methods chainable; standardize result column naming.
- **Lucid**: Allow `null` eager-loaded relations in strict mode without throwing exceptions.
- **Console**: Standardize spacing in `ServeCommand`; fix label padding; remove redundant newlines in create commands; add newline after each migration output.

## [0.9.2] - 2026-05-15

- Fix: Add foreign key to existing table via `alterTable()->add()`.

## [0.9.1] - 2026-05-14

- Fix: Standardize spacing in conditional statement for auth config check.

## [0.9.0] - 2026-05-14

- Dropped alpha tag.
- CI (8.2–8.5), PHP-CS-Fixer, open source docs.
- Fixed: cross-platform exit codes, flaky job tests, PHP 8.5+ OpenSSL compat.