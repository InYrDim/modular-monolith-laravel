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

The host is a composition root; domain code lives in `modules/<Name>`. See `docs/architecture/modules.md` for the layout and the full module checklist.

## Boundaries

- Code lives in `modules/<Name>/src` (namespace `Modules\<Name>`); routes, migrations, factories, seeders, and tests live in the module.
- Modules never import `App\`. Module controllers do not extend `App\Http\Controllers\Controller`.
- A module is used by others only through its `Contracts` namespace (interfaces, DTOs, events). Never its Models, Actions, Concerns, Http, or Providers.
- No Eloquent relationships across modules; store the foreign ID and fetch through a Contract.
- Identity never depends on business modules. Create a Contract only when a real consumer exists.
- Never rename or edit an existing migration file; new migrations go in the owning module.
- Module routes load inside the `web` group from the module's service provider.

## Adding a feature

1. Decide the home: cross-cutting glue stays in the host (`app/`, `routes/web.php`, `resources/js/pages/<area>/`, `tests/Feature/`); a bounded context with its own data, routes, and UI becomes a module under `modules/<Name>/`. Do not create a module for a single endpoint.
2. Host feature: follow the existing controller, Form Request, Inertia page, and feature-test patterns.
3. New module: create `modules/<Name>/{src,database,routes,tests}`, add PSR-4 mappings to `composer.json`, create `<Name>ServiceProvider` (migrations, `web` routes, bindings) and register it in `bootstrap/providers.php`, add the test suite to `phpunit.xml` and to `tests/Pest.php`, then run `composer dump-autoload`.
4. Existing module feature: keep controllers, requests, actions, routes, migrations, and tests inside that module; reach another module only through its `Contracts`.
5. Frontend: Inertia page names are layout keys in `resources/js/app.tsx` (`auth/`, `settings/`, `welcome`); do not rename them. Module pages go in `resources/js/pages/<name>/`. Regenerate Wayfinder after moving controllers.
6. Add or update a feature test for behavior changes, then run Pint, PHPStan, Pest, `npm run types:check`, and the frontend build.
