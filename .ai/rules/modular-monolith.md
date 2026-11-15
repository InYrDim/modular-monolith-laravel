---
paths:
  - 'modules/**'
  - 'app/**'
  - 'tests/Arch/**'
  - 'composer.json'
---

# Modular monolith rules

- Code lives in `modules/<Name>/src` (namespace `Modules\<Name>`); routes, migrations, factories, seeders, and tests live in the module.
- Modules never import `App\`. Module controllers do not extend `App\Http\Controllers\Controller`.
- A module is used by others only through its `Contracts` namespace (interfaces, DTOs, events). Never its Models, Actions, Concerns, Http, or Providers.
- No Eloquent relationships across modules; store the foreign ID and fetch through a Contract.
- Identity never depends on business modules. Create a Contract only when a real consumer exists.
- Never rename or edit an existing migration file; new migrations go in the owning module.
- Module routes load inside the `web` group from the module's service provider.
- Inertia page names are layout keys in `resources/js/app.tsx` (`auth/`, `settings/`, `welcome`); do not rename them. Pages for a new module go in `resources/js/pages/<name>/`.
- Regenerate Wayfinder after moving controllers; controller-action imports follow PHP namespaces.
- New modules need entries in `composer.json`, `bootstrap/providers.php`, `phpunit.xml`, and `tests/Pest.php`. PHPStan already covers `modules/`.
- Boundary tests live in `tests/Arch/ModuleBoundariesTest.php`; run `composer ci:check` before finishing any task.
