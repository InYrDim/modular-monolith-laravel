# Starting a project from this template

This repository is a modular-monolith starter. This branch is already cleaned for a real project: the `Billing` example module, the seeded test user, and the placeholder tests are gone. Do the remaining steps once, before product code.

## 1. Own the code

Copy the project instead of building inside the template directory, and start a fresh history:

```bash
cp -r modular-monolith-laravel my-product
cd my-product
rm -rf .git
git init && git add -A && git commit -m "chore: initial commit from template"
```

Keep the original history only if you want the template's commits; a fresh history makes `git blame` and migrations easier to reason about.

## 2. Rename

- `composer.json` → `"name": "your-vendor/your-product"`
- `.env` and `.env.example` → `APP_NAME`, `APP_URL` (Herd: `https://<directory>.test`)
- `README.md` → describe your product, not the starter kit
- Herd creates the site from the directory name; rename the directory before adding the site

## 3. Review auth defaults

`config/fortify.php` enables registration, password reset, email verification, two-factor authentication, and passkeys. Disable what the product does not allow — `Features::registration()` for invite-only products, passkeys if you only support passwords. Keep `emailVerification` and `twoFactorAuthentication` unless you have a reason not to.

## 4. Remove remaining placeholders

- `resources/js/pages/welcome.tsx` — marketing placeholder; replace with your landing page or delete it and the `home` route
- `app/Http/Middleware/HandleAppearance.php` — light/dark mode plumbing; keep only if the product supports it
- `tests/Arch/*` — keep all of it; the boundary tests are the architecture

## 5. Agent tooling

The template carries per-tool copies of the same skills. Keep every directory your tools read:

- `.ai/rules/` — project rules (the source of truth for agents)
- `.agents/` — shared skills, including `security-audit`
- `.claude/` — Claude Code skills
- `.pi/` — skills for the pi harness
- `AGENTS.md` and `CLAUDE.md` — agent instructions; do not delete them

The skill directories are duplicated per tool on purpose. Update them together when a skill changes (`skills-lock.json` records the installs).

## 6. Decide infrastructure now

| Concern  | Default in template | Decide before launch                           |
| -------- | ------------------- | ---------------------------------------------- |
| Database | SQLite              | Postgres or MySQL, and a migration plan        |
| Cache    | `database`          | Redis if you run more than one app server      |
| Session  | `database`          | Redis for horizontal scaling                   |
| Queue    | `database`          | Redis; run a worker and a scheduler            |
| Mail     | `log`               | A real provider with a verified sending domain |

## 7. First run

```bash
composer setup   # installs, creates .env, migrates, builds assets
composer dev     # server, queue, logs, Vite
```

The first `migrate` creates `database/database.sqlite` if it does not exist.

## 8. Before production

- `APP_ENV=production`, `APP_DEBUG=false`, `php artisan optimize` in the deploy step. The environment name is not cosmetic: `production` is what enables the strict password policy (12+ characters, mixed case, symbols, breach check), the destructive-command guard, and `https` on generated URLs
- `APP_URL` set to the real public origin. It pins the root of every absolute URL (password reset, email verification, redirects) and `trustHosts()` derives its allowlist from its host
- `SESSION_SECURE_COOKIE=true`, plus `SESSION_DOMAIN` and `SESSION_SAME_SITE` if the session has to cross subdomains
- `TRUSTED_PROXIES` set to the load balancer or proxy ranges when TLS terminates upstream, so the application sees the original scheme. Never set it to `*` on an internet-reachable host
- Host trust: `bootstrap/app.php` enables `trustHosts()`, which trusts `APP_URL` and its subdomains. It is inactive while `APP_ENV=local`; in every other environment a wrong `APP_URL` rejects every request with HTTP 400
- Queue worker and scheduler running (Supervisor or Laravel Cloud)
- Real mail transport, verified domain, and a test send
- Backups for the database and `storage/`
- Error tracking (Sentry, Flare) and log retention
- Authorization: policies are discovered per module by convention (`Modules\<Name>\Policies\<Model>Policy`), but there is **no roles/permissions model** yet — design it early if the product needs it

Smoke-check the host and URL configuration after the first deploy; neither request needs an account:

```bash
curl -s -o /dev/null -w '%{http_code}\n' https://example.com/up                        # 200
curl -s -o /dev/null -w '%{http_code}\n' -H 'Host: evil.test' https://example.com/up   # 400
```

A 200 on the second line means host trust is not in effect — usually because `APP_ENV` is still `local` or `APP_URL` has no host. A 400 on the first line means `APP_URL` does not match the host the load balancer sends.

## 9. What this template deliberately does not include

- **API scaffolding.** No `routes/api.php` and no Sanctum. If a module needs an API, decide the host contract first (version prefix, throttling, guard, CSRF stance) as described in `docs/architecture/modules.md`.
- **Events implementation.** The cross-module event convention is documented, but no event, listener, or outbox exists until a module needs one.
- **A shared module kernel.** Provider boilerplate is duplicated per module on purpose; revisit at four or five modules.

## Starting from the template's `main` branch instead

If your checkout still contains the `Billing` example module, remove it together with its contract — the consumer is what justified the contract:

```bash
rm -rf modules/Billing resources/js/pages/billing
rm -rf modules/Identity/src/Contracts modules/Identity/src/Queries
composer dump-autoload
php artisan wayfinder:generate --with-form && npm run build
```

Then remove the `IdentityServiceProvider::register()` binding, the `Billing` PSR-4 entries in `composer.json`, `BillingServiceProvider` from `bootstrap/providers.php`, and the `Billing` mentions in `docs/` and `README.md`.
