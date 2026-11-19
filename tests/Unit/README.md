# Unit tests

This template ships without unit tests on purpose (see
`.ai/rules/modular-monolith.md`: no placeholder tests). The `Unit` testsuite exists in `phpunit.xml` so the
directory does not have to be created before the first unit test lands.

Unit tests placed here run on PHPUnit's `TestCase` and get no application
container. Anything that needs the framework, the database, or the HTTP stack
belongs in `tests/Feature/`, which `tests/Pest.php` binds to `Tests\TestCase`
with `RefreshDatabase`.
