# Laravel React Starter Kit

A Laravel 13 application with an Inertia v3 + React 19 frontend, organized as a modular monolith. Authentication and account settings live in `modules/Identity`; the host application stays a thin composition root.

## Stack

| Layer    | Technology                                     |
| -------- | ---------------------------------------------- |
| Backend  | PHP 8.4+, Laravel 13, Laravel Fortify          |
| Frontend | React 19, TypeScript, Inertia v3               |
| Styling  | Tailwind CSS 4, Radix UI, Sonner               |
| Build    | Vite+ (`vp`), Laravel Wayfinder                |
| Quality  | Pest 5, PHPStan/Larastan level 7, Laravel Pint |

## Requirements

- PHP 8.4 or newer (CI uses 8.4)
- Composer 2
- Node.js 22 and npm
- SQLite (default) or another Laravel-supported database

## Getting started

On a fresh checkout, create the SQLite database file and let the setup script install dependencies, create `.env`, generate the key, migrate, and build the frontend:

```bash
touch database/database.sqlite
composer setup
composer dev
```

`composer dev` starts the development processes: the HTTP server, Vite, a queue listener, and logs when the platform supports them. Open the URL it prints, by default <http://localhost:8000>.

### Manual setup

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm install
npm run build
```

Then start the server and Vite in separate terminals:

```bash
php artisan serve
npm run dev
```

If Laravel Herd serves the project, use the Herd site URL for this directory (for example `https://<directory>.test`) instead of `php artisan serve`, and run `npm run dev` for assets.

## Authentication

Fortify provides registration, login, email verification, password reset, password confirmation, two-factor authentication with recovery codes, and passkeys. The settings area supports profile updates, password changes, and appearance preferences.

## Architecture

One deployable Laravel application with explicit module boundaries:

```text
app/                      Host glue: base controller, middleware, service provider
modules/Identity/         Users, auth, passkeys, 2FA, profile and security settings
modules/Billing/          Invoices; reference module consuming Identity\Contracts\UserDirectory
bootstrap/providers.php   Registers host and module service providers
routes/web.php            Host routes (home, dashboard)
resources/js/             React pages, layouts, components, generated Wayfinder code
tests/                    Host feature/unit tests and architecture tests
docs/architecture/        Module layout and checklists
.ai/rules/                Path-scoped rules for AI agents
```

Boundary rules:

- Module code never imports `App\`.
- Modules use each other only through `Modules\<Name>\Contracts`.
- No Eloquent relationships across modules: store the foreign ID and fetch through a contract.
- `tests/Arch/ModuleBoundariesTest.php` enforces these rules for every directory under `modules/`.

See [docs/architecture/modules.md](docs/architecture/modules.md) for the full layout and the new-module checklist. Agent instructions live in [.ai/rules/modular-monolith.md](.ai/rules/modular-monolith.md), indexed by `.ai/rules/index.md`; `AGENTS.md` points agents there.

### Adding a feature

- Cross-cutting glue stays in the host: controllers under `app/Http/Controllers`, routes in `routes/web.php`, pages in `resources/js/pages/<area>/`, tests in `tests/Feature/`.
- A bounded context with its own data, routes, and UI becomes a module under `modules/<Name>/`. Scaffold it with `php artisan make:module <Name>`; the command creates the module, registers its provider, and adds the PSR-4 mappings.
- Keep controllers, requests, actions, routes, migrations, and tests inside the owning module.
- Reach another module only through its `Contracts`, defined when the first consumer needs them: `php artisan make:contract <Module> <Name>` (add `--dto=<Name>` for a readonly DTO). Do not predefine contracts.
- Regenerate Wayfinder after moving controllers (`php artisan wayfinder:generate --with-form`); the Vite plugin also regenerates during builds.

## Common commands

| Command                | Description                                         |
| ---------------------- | --------------------------------------------------- |
| `composer setup`       | Install dependencies, create `.env`, migrate, build |
| `composer dev`         | Start the development processes                     |
| `composer lint`        | Fix PHP style with Pint                             |
| `composer lint:check`  | Check PHP style                                     |
| `composer types:check` | Run PHPStan                                         |
| `composer test`        | Pint check, PHPStan, and Pest                       |
| `composer ci:check`    | Frontend check and types, then `composer test`      |
| `npm run dev`          | Start Vite                                          |
| `npm run build`        | Production client build                             |
| `npm run build:ssr`    | Client and SSR builds                               |
| `npm run types:check`  | TypeScript type check                               |
| `npm run check`        | Frontend format/lint check                          |
| `npm run check:fix`    | Apply frontend fixes                                |

### Tests

```bash
php artisan test --compact
php artisan test --compact tests/Feature/DashboardTest.php
php artisan test --compact --filter=testName
```

Module tests are discovered automatically: the `Modules` suite in `phpunit.xml` scans `modules`, and `tests/Pest.php` binds `Tests\TestCase` to `../modules/*/tests`. Architecture tests live under `tests/Arch`.

## License

MIT
