---
paths:
    - 'modules/**'
    - 'app/**'
    - 'bootstrap/**'
    - 'routes/**'
    - 'database/**'
    - 'resources/js/**'
    - 'tests/**'
    - 'config/auth.php'
    - 'composer.json'
    - 'phpunit.xml'
---

# Modular monolith rules

The host is a composition root; domain code lives in `modules/<Name>`. See `docs/architecture/modules.md` for the layout and the full module checklist, and `docs/getting-started.md` for the one-time project bootstrap (rename, auth features, infrastructure).

## Starting a product

`main` is the clean template: no example module, no seeded test user, no placeholder tests. Never add demo code to `main` or to a product built from it. The worked cross-module example (`Billing` reading users through an Identity contract) lives only on the `example/billing` branch; read it with `git diff main origin/example/billing` when a reference is needed, and never merge or cherry-pick it.

## Boundaries

- Code lives in `modules/<Name>/src` (namespace `Modules\<Name>`); routes, migrations, factories, seeders, and tests live in the module.
- Modules never import `App\`. Module controllers do not extend `App\Http\Controllers\Controller`.
- A module is used by others only through its `Contracts` namespace (interfaces, DTOs, events). Never its Models, Actions, Concerns, Http, or Providers.
- No Eloquent relationships across modules; store the foreign ID and fetch through a Contract.
- Identity never depends on business modules.
- Contracts are consumer-driven. Do not add `src/Contracts` while no other module uses the module; a contract is defined when its first real consumer arrives, in the same change as that consumer. Create one with `php artisan make:contract <Module> <Name>` (add `--dto=<Name>` for a readonly DTO), declare only the methods the consumer needs, return `Contracts\Dto` objects instead of models, and bind an implementation in the module provider. This includes `Identity`: leave it without `Contracts` until something consumes it.
- Do not add a `--contract` option to `make:module`. A contract created at module-creation time has no consumer, so it is pure speculation; `make:contract` adds one to an existing module at the moment the consumer lands. Do not scaffold `src/Contracts` at all in new modules.
- Never rename or edit an existing migration file; new migrations go in the owning module.
- Module routes load inside the `web` group from the module's service provider.
- Cross-module reactions use events published under `Contracts\Events`, and the consumer registers its listener explicitly in its provider. Module listeners are not auto-discovered, so an unregistered listener fails silently.
- Module pages in `resources/js/pages/<module>/` never import another module's `@/actions/Modules/<Other>/*`; use that module's routes or a PHP contract. `tests/Arch/FrontendBoundaryTest.php` enforces it.
- Host migrations create infrastructure tables only; domain tables belong in the owning module's migrations. `tests/Arch/MigrationOwnershipTest.php` enforces it.

## Module provider

`make:module` generates a minimal provider: module migrations plus `web` routes. Do not add wiring for things the module does not have yet — no translations, views, config, commands, listeners, or policies pointing at files that do not exist. Add each one only when the module gains it:

| Need               | Registration                                                                                                                                    |
| ------------------ | ----------------------------------------------------------------------------------------------------------------------------------------------- |
| Translations       | `loadTranslationsFrom($dir, '<name>')`                                                                                                          |
| Views              | `loadViewsFrom($dir, '<name>')`                                                                                                                 |
| Config             | `mergeConfigFrom($path, '<name>')` — create the config file first, it is `require`d                                                             |
| Console commands   | `$this->commands([...])` — module commands are **not** auto-discovered (only `app/Console/Commands`)                                            |
| Event listeners    | `Event::listen(Event::class, Listener::class)` in the provider — module listeners are **not** auto-discovered (only `app/Listeners`)            |
| Policies           | none if placed at `src/Policies/<Model>Policy.php` (matches the guessed `Modules\<Name>\Policies\<Model>Policy`); otherwise `Gate::policy(...)` |
| Broadcast channels | `Broadcast::channel(...)`                                                                                                                       |
| Blade components   | `Blade::componentNamespace(...)`                                                                                                                |

No API route file is generated, because the host has not decided its API contract. `bootstrap/app.php` does not call `withRouting(api: ...)`, the `api` group has no throttling (`apiLimiter` is unset), there is no versioning or token auth (Sanctum is not installed), and `PreventRequestForgery` only runs in the `web` group — so a session-authenticated `api` route would be CSRF-exposed. If a module needs an API, settle those host-level concerns first (prefix or version, rate limiter, auth guard, CSRF stance, success payload shape), then add `routes/api.php` and register it in the module provider.

## Adding a feature

1. Decide the home: cross-cutting glue stays in the host (`app/`, `routes/web.php`, `resources/js/pages/<area>/`, `tests/Feature/`); a bounded context with its own data, routes, and UI becomes a module under `modules/<Name>/`. Do not create a module for a single endpoint.
2. Host feature: follow the existing controller, Form Request, Inertia page, and feature-test patterns.
3. New module: run `php artisan make:module <Name>`. It creates `modules/<Name>/{src,database,routes,tests}`, adds the PSR-4 mappings to `composer.json`, registers `<Name>ServiceProvider` in `bootstrap/providers.php`, and runs `composer dump-autoload`. Module tests are discovered automatically (`modules` suite in `phpunit.xml`, `../modules/*/tests` in `tests/Pest.php`), so no test registration is needed.
4. Existing module feature: keep controllers, requests, actions, routes, migrations, and tests inside that module; reach another module only through its `Contracts`. `tests/Arch/ModuleBoundariesTest.php` also fails when a module provider is missing from `bootstrap/providers.php` or when a module's `Contracts` import that module's `Models`, `Actions`, `Http`, `Concerns`, or `Providers`.
5. Cross-module need: do not reach into another module's `Models`, `Actions`, or `Http`. Run `php artisan make:contract <OwningModule> <Name>` in the same change as the consumer, add only the methods that consumer uses, implement it inside the owning module, and bind it in that module's provider. Do not define contracts speculatively — shape follows the consumer, not the database.
6. Frontend: frontend stays in the host, not in modules. `make:module` intentionally does not create `resources/js/pages/<name>/`; add module pages there yourself and never move pages, components, or actions into `modules/`. Inertia page names are layout keys in `resources/js/app.tsx` (`auth/`, `settings/`, `welcome`); do not rename them. Regenerate Wayfinder after moving controllers.
7. Add or update a feature test for behavior changes, then run Pint, PHPStan, Pest, `npm run types:check`, and the frontend build. PHPStan analyses `tests/` as well, so keep test code type-clean.
