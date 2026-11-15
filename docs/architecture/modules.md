# Modular Monolith

This application is a modular monolith: a single Laravel codebase, one deployment, and one database, with module boundaries enforced by automated tests.

## Modules

| Module     | Responsibility                                                                                      | Public contracts |
| ---------- | --------------------------------------------------------------------------------------------------- | ---------------- |
| `Identity` | Authentication (Fortify), users, passkeys, two-factor authentication, profile and security settings | None yet         |

Modules expose code to other modules only through their `src/Contracts` namespace. `Identity` has no consumers outside the host composition root, so it has no contracts yet.

## Layout

`modules/<Name>/` owns one bounded context:

| Path                                       | Purpose                                                                         |
| ------------------------------------------ | ------------------------------------------------------------------------------- |
| `src/`                                     | Module code, PSR-4 namespace `Modules\<Name>`                                   |
| `src/Contracts/`                           | Public API: interfaces, DTOs, events (only when a real consumer exists)         |
| `src/Providers/<Name>ServiceProvider.php`  | Registers routes, migrations, and bindings; listed in `bootstrap/providers.php` |
| `database/migrations/`                     | Migrations owned by the module, loaded by the provider                          |
| `database/factories/`, `database/seeders/` | Module data helpers, PSR-4 under `Modules\<Name>\Database\...`                  |
| `routes/web.php`                           | Routes loaded inside the `web` middleware group by the provider                 |
| `tests/`                                   | Module tests, discovered through `phpunit.xml` and `tests/Pest.php`             |

The host keeps framework glue only:

| Path                                           | Role                                            |
| ---------------------------------------------- | ----------------------------------------------- |
| `app/Http/Controllers/Controller.php`          | Empty base controller; modules do not extend it |
| `app/Http/Middleware/`                         | `HandleAppearance`, `HandleInertiaRequests`     |
| `app/Providers/AppServiceProvider.php`         | Cross-cutting defaults                          |
| `routes/web.php`, `routes/console.php`         | Host routes and console commands                |
| `database/migrations/`                         | Infrastructure migrations (cache, jobs)         |
| `database/seeders/DatabaseSeeder.php`          | Calls module seeders                            |
| `tests/Feature/`, `tests/Unit/`, `tests/Arch/` | Host tests and boundary tests                   |
| `resources/`                                   | Shared Inertia/React frontend (not modularized) |

## Dependency rules

1. Module code never depends on the host namespace `App\`.
2. Modules use another module only through `Modules\<Name>\Contracts`, never its models, actions, concerns, HTTP classes, or providers.
3. The host `App\` namespace uses modules only through `Contracts`. Composition-root code outside `App\` (`bootstrap/`, `config/`, `routes/`, `database/seeders/DatabaseSeeder.php`, `tests/`) may reference module classes.
4. No Eloquent relationships across modules: store the foreign ID and fetch through a contract.
5. `Identity` never depends on business modules.
6. Create a contract only when a real consumer exists.

`tests/Arch/ModuleBoundariesTest.php` enforces rules 1–3 and the Identity rule for every `modules/*` directory. It is built dynamically, so new modules are covered automatically.

## Routes, pages, and frontend

- Module routes load inside the `web` group from the module provider, so `HandleAppearance`, `HandleInertiaRequests`, and `AddLinkHeadersForPreloadedAssets` still apply.
- Inertia page names are layout keys in `resources/js/app.tsx` (`auth/`, `settings/`, `welcome`); do not rename them.
- Frontend pages stay in `resources/js/pages/<area>/`, not inside modules.
- Wayfinder output is generated: run `php artisan wayfinder:generate --with-form` (or `npm run build`) after moving controllers, then update controller-action imports to follow PHP namespaces.

## Adding a module

1. Create `modules/<Name>/` with `src/`, `database/migrations/`, `routes/`, and `tests/`.
2. Add PSR-4 entries to `composer.json` for `Modules\<Name>\`, plus `Database\Factories` and `Database\Seeders` if used; run `composer dump-autoload`.
3. Create `<Name>ServiceProvider` with `loadMigrationsFrom`, route loading in the `web` group, and any bindings; register it in `bootstrap/providers.php`.
4. Add `modules/<Name>/tests` to `phpunit.xml` and to the `->in(...)` list in `tests/Pest.php`.
5. Own your tables: migrations live in the module. Never rename an existing migration file.
6. Expose only what other modules need under `src/Contracts`.
7. Add frontend pages under `resources/js/pages/<name>/`; add a layout rule in `app.tsx` only if the default `AppLayout` is wrong.
8. Run `composer ci:check` (or the focused Pint, PHPStan, Pest, and frontend checks).
