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
6. Create a contract only when a real consumer exists, in the same change as that consumer (see [Contracts](#contracts)).

`tests/Arch/ModuleBoundariesTest.php` enforces rules 1–3 and the Identity rule for every `modules/*` directory. It also asserts that every module provider is registered in `bootstrap/providers.php`, and that a module's `Contracts` do not depend on that module's internals. The test is built dynamically, so new modules are covered automatically.

## Contracts

Contracts are consumer-driven. A module exposes nothing until another module needs something from it; define the contract when the first consumer arrives, in the same change as that consumer. A contract written before its consumer is a guess: it is shaped by the producer's tables instead of the consumer's needs, and every speculative method becomes API that is expensive to change. Where a contract is needed, its shape follows the consumer, not the database: declare only the methods the consumer uses and return `Contracts\Dto` objects rather than Eloquent models.

```bash
php artisan make:contract Identity UserDirectory                    # interface only
php artisan make:contract Identity UserDirectory --dto=UserSummary  # plus a readonly DTO
```

The command creates `modules/<Name>/src/Contracts/<Contract>.php` (and `Contracts/Dto/<Dto>.php`) in an existing module and refuses to overwrite an existing contract. `make:module` deliberately has no `--contract` option: a contract created together with an empty module has no consumer and would be shaped by the module's tables instead of a consumer's needs. Implementing the contract, binding it in the module provider, and writing the consumer test stay manual because only the consumer knows the required methods. Once `src/Contracts` exists, `tests/Arch/ModuleBoundariesTest.php` enforces that the contracts do not depend on that module's `Models`, `Actions`, `Http`, `Concerns`, or `Providers`.

When the consumer disappears, remove the contract rather than leaving a public API nobody calls.

## Routes, pages, and frontend

- Module routes load inside the `web` group from the module provider, so `HandleAppearance`, `HandleInertiaRequests`, and `AddLinkHeadersForPreloadedAssets` still apply.
- Inertia page names are layout keys in `resources/js/app.tsx` (`auth/`, `settings/`, `welcome`); do not rename them.
- Frontend pages stay in `resources/js/pages/<area>/`, not inside modules. The module generator does not scaffold frontend pages; create `resources/js/pages/<name>/` yourself when the module gets a UI, and never move pages, components, or generated actions into `modules/`.
- Wayfinder output is generated: run `php artisan wayfinder:generate --with-form` (or `npm run build`) after moving controllers, then update controller-action imports to follow PHP namespaces.

## Adding a module

Scaffold the module instead of creating files by hand:

```bash
php artisan make:module Billing        # add --api to also scaffold routes/api.php
```

The command creates `modules/Billing/{src,database,routes,tests}`, a `BillingServiceProvider` that loads the module migrations and its `web` routes (plus `api` routes with `--api`), adds the PSR-4 mappings to `composer.json`, registers the provider in `bootstrap/providers.php`, and runs `composer dump-autoload` (skip that step with `--no-autoload`).

After scaffolding:

1. Put module code under `modules/<Name>/src` with the `Modules\<Name>` namespace.
2. Expose only what other modules need under `src/Contracts` (interfaces, DTOs, events), and bind implementations in the module provider. Create a contract only when a real consumer exists.
3. Own your tables: migrations live in `modules/<Name>/database/migrations`. Never rename an existing migration file.
4. Add frontend pages under `resources/js/pages/<name>/`; add a layout rule in `app.tsx` only if the default `AppLayout` is wrong.
5. Run `composer ci:check` (or the focused Pint, PHPStan, Pest, and frontend checks).

Tests are discovered automatically: `phpunit.xml` runs the `Modules` suite from the `modules` directory, and `tests/Pest.php` binds `Tests\TestCase` to `../modules/*/tests`. No registration step is needed for a new module.
