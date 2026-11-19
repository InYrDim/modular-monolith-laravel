---
name: project-bootstrap
description: 'ACTIVATE when the user wants to start a new product from this modular-monolith template: resetting a cloned copy into a clean project, removing the template git history, renaming the app (composer name, APP_NAME, APP_URL, README), reviewing auth defaults, removing placeholder files, or choosing infrastructure before first run. Use when the user says "clean", "setup new project", "clone", "rename project", "bootstrap", or references docs/getting-started.md. Do NOT use for everyday feature work, adding modules, or auth/UI changes.'
license: MIT
---

# Project Bootstrap (Clean New Project from Template)

Turn a clone of this template into a fresh product repository. The canonical guide is `docs/getting-started.md` at the project root; this skill is the condensed, step-by-step checklist.

## 1. Own the code

Copy the project out of the template directory and start a fresh git history:

```bash
cp -r modular-monolith-laravel my-product
cd my-product
rm -rf .git
git init && git add -A && git commit -m "chore: initial commit from template"
```

Keep the original history only if the template's commits are wanted; a fresh history makes `git blame` and migrations easier to reason about.

## 2. Rename

- `composer.json` → `"name": "your-vendor/your-product"`
- `.env` and `.env.example` → `APP_NAME`, `APP_URL` (Herd: `https://<directory>.test`)
- `README.md` → describe the product, not the starter kit
- Herd creates the site from the directory name; rename the directory before adding the site

## 3. Review auth defaults

`config/fortify.php` enables registration, password reset, email verification, two-factor authentication, and passkeys. Disable what the product does not allow — `Features::registration()` for invite-only products, passkeys if only passwords. Keep `emailVerification` and `twoFactorAuthentication` unless there is a reason not to.

## 4. Remove remaining placeholders

- `resources/js/pages/welcome.tsx` — marketing placeholder; replace with the landing page or delete it and the `home` route
- `app/Http/Middleware/HandleAppearance.php` — light/dark mode plumbing; keep only if the product supports it
- `tests/Arch/*` — keep all of it; the boundary tests are the architecture

## 5. Agent tooling — keep, do not delete

- `.ai/rules/` — project rules (source of truth for agents)
- `.agents/` — shared skills, including `security-audit`
- `.claude/` — Claude Code skills
- `.pi/` — skills for the pi harness
- `AGENTS.md` and `CLAUDE.md` — agent instructions

The skill directories are duplicated per tool on purpose. Update them together when a skill changes.

## 6. Decide infrastructure now

| Concern  | Default in template | Decide before launch                           |
| -------- | ------------------- | ---------------------------------------------- |
| Database | SQLite              | Postgres or MySQL, and a migration plan        |
| Cache    | `database`          | Redis if more than one app server              |
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

- `APP_ENV=production`, `APP_DEBUG=false`, `php artisan optimize` in the deploy step
- `APP_URL` set to the real public origin
- `SESSION_SECURE_COOKIE=true`, plus `SESSION_DOMAIN` and `SESSION_SAME_SITE` if crossing subdomains
- `TRUSTED_PROXIES` for upstream TLS termination; never `*` on an internet-reachable host
- Queue worker and scheduler running
- Real mail transport, verified domain, test send
- Backups for the database and `storage/`
- Error tracking and log retention
- Authorization: no roles/permissions model exists yet — design it early if needed

Smoke-check after the first deploy:

```bash
curl -s -o /dev/null -w '%{http_code}\n' https://example.com/up                        # 200
curl -s -o /dev/null -w '%{http_code}\n' -H 'Host: evil.test' https://example.com/up   # 400
```

A 200 on the second line means host trust is not in effect. A 400 on the first line means `APP_URL` does not match the host the load balancer sends.

## Starting from a checkout that still has the Billing example module

If the checkout still contains the `Billing` example module, remove it together with its contract:

```bash
rm -rf modules/Billing resources/js/pages/billing
rm -rf modules/Identity/src/Contracts modules/Identity/src/Queries
composer dump-autoload
php artisan wayfinder:generate --with-form && npm run build
```

Then remove the `IdentityServiceProvider::register()` binding, the `Billing` PSR-4 entries in `composer.json`, `BillingServiceProvider` from `bootstrap/providers.php`, and the `Billing` mentions in `docs/` and `README.md`.
