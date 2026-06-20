# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A **minimal Laravel 13 e-commerce catalog** (products + search; no cart/payments) built to **validate a full stack on Plesk shared hosting**: Blade SSR + htmx + Alpine + Tailwind v4 + PostgreSQL (Eloquent) + Meilisearch (Scout) + Redis. It is deployed live (lab138/lab139.littlebigpro.com). Domain identifiers are in Spanish (`Producto`, `/buscar`, `/subir`, `/cuenta`).

> **Before anything deployment-related, read `docs/DESPLIEGUE-PLESK.md`** — a Toolkit-first playbook capturing hard-won Plesk gotchas. `docs/comandos-mantenimiento.md` has copy-paste server commands; `deploy/verify.sh` is a live smoke test.

## Commands

Local first run:
```
composer install
cp .env.example .env && php artisan key:generate
# edit .env: DB_* (PostgreSQL) + MEILISEARCH_HOST/KEY
php artisan migrate --seed
php artisan scout:sync-index-settings && php artisan scout:import "App\Models\Producto"
npm install && npm run build
```

- **Run:** `php artisan serve` — or `composer dev` (serve + queue + pail + vite concurrently).
- **Tests:** `composer test` or `php artisan test`; single test: `php artisan test --filter=TestName`.
- **Format:** `vendor/bin/pint`.
- **Build assets:** `npm run build` regenerates `public/build`. Run it after ANY Blade/Tailwind class change, then **commit `public/build`** (see Conventions).
- **Reindex search** (after changing `toSearchableArray()` or `config/scout.php`): `php artisan scout:sync-index-settings && php artisan scout:import "App\Models\Producto"`.

## Deployment (B1 — compile locally, ship via Git)

**The chosen model: the dev machine compiles; Plesk only pulls and fixes state. Plesk never runs Node or a build step.** Full Toolkit playbook (validated on lab138): `docs/DESPLIEGUE-PLESK.md`.

**Who does what:**
- **Dev machine** compiles assets (`npm run build` → `public/build`, which is **committed**). `vendor/` is **not** committed.
- **GitHub** is the transport (+ history / rollback).
- **Plesk pulls on push and runs the recurring deploy actions automatically** — `migrate --force && scout:sync-index-settings && optimize`. So `optimize` is **not** a manual step; it runs on every deploy. `composer install` runs only when `composer.json` changes (Toolkit → Composer).

**Per-change flow (the everyday loop):**
1. Edit code / views / styles.
2. `npm run build` — **only if** you touched Blade classes, Tailwind, or JS (assets). Pure PHP changes skip this.
3. Commit explicit paths (incl. `public/build` when rebuilt) and `git push`.
4. Plesk pulls → runs migrate + scout:sync + **optimize** by itself. Done.

**One-time per app (bootstrap — NEVER in the recurring deploy actions):** create the PostgreSQL DB, write `.env`, `key:generate`, `migrate --seed`, `scout:import`, `storage:link`, Scheduler/Queue toggles, and Composer → Install. See playbook §5–§9.

**Manual step needed only when:** dependencies change (`composer.json` → Toolkit Composer Install), the catalog/seeder changes (`scout:import` once), or `.env` changes (`optimize`). Everything else is just `git push`.

## Stack & services (what we have, how to reach them)

Every service runs on the Plesk host and is **shared across apps** — per-app namespacing keeps them from colliding. Full values + credentials live in `docs/DESPLIEGUE-PLESK.md` (§5–§6 `.env`, §12 isolation, §13 SMTP, + the reference-values block). **Never hardcode hosts/keys in code — read them from `.env`.**

- **PostgreSQL** (Eloquent) — `127.0.0.1:5432`. DBs and users carry the `cmurillo_` prefix (created in Plesk → Databases). Needs the `pdo_pgsql` PHP driver on. Config via `DB_*` in `.env`.
- **Meilisearch** (search, via Scout) — `http://127.0.0.1:7700`, **shared**. Master key is in `/etc/meilisearch.toml` on the server (`grep master_key`) → set as `MEILISEARCH_KEY`. Isolated per app with `SCOUT_PREFIX` → index name `<prefix>productos`. Reached only through `app/Services/BuscadorProductos.php` (Scout `->raw()`), with a PostgreSQL `ilike` fallback. `SCOUT_QUEUE=false` ⇒ synchronous indexing.
- **Redis** — `127.0.0.1:6379` via `phpredis`; cache lives on **db 1**. Isolated with `REDIS_PREFIX` (plus Laravel's own `APP_NAME` separation). Backs both cache (`CACHE_STORE=redis`) and the queue (`QUEUE_CONNECTION=redis`).
- **SMTP** (mail) — a Plesk mailbox per domain (host = the domain; 465/`smtps` or 587/`smtp` via `MAIL_SCHEME`). `MAIL_*` in `.env`; the mailbox password is an **external** secret → server `.env` only, never the repo. Smoke test: `php artisan mail:test <to>`.
- **Queue + Scheduler** — Plesk has no daemon, so a scheduler-driven worker in `routes/console.php` runs `queue:work --stop-when-empty` every minute. Requires the **Scheduler toggle** on and `QUEUE_CONNECTION=redis`. Diagnostics: `/infra`, `/infra/dispatch`, `/infra/upload-test`.

## Architecture (the parts that span files)

**Search is the core.** `app/Services/BuscadorProductos.php` is the single search entry point, used by both the home (`HomeController`) and the htmx endpoint (`BuscarController`):
- Queries Meilisearch via Scout `->raw()` to obtain hits + `processingTimeMs` + `facetDistribution`.
- Runs **two** Meili queries: filtered hits, and facet counts *without* the category filter (so the user can switch categories — disjunctive facets).
- On any Meili exception it **falls back to PostgreSQL** (Eloquent `ilike`) so the page never dies.
- Returns a uniform view-model rendered by `resources/views/partials/resultados.blade.php`.

**htmx partial swap.** `/buscar` returns ONLY `partials/resultados` (grid + facet chips + a hidden `#categoria-actual`), which htmx swaps into `#resultados`. The search input and the chips carry each other's state via `hx-include`. `partials/card.blade.php` uses array access (`$p['nombre']`) so it works for both Eloquent models (home/fallback) and Meili hit arrays (search) — `toSearchableArray()` includes `imagen_url` so cards render from Meili hits with no DB round-trip.

**Multi-tenant search isolation.** The `Producto` model **deliberately does not override `searchableAs()`**, so Scout's default `config('scout.prefix').getTable()` applies → the index name is `SCOUT_PREFIX + 'productos'`. Meilisearch and Redis are shared across apps on the server; `SCOUT_PREFIX` / `REDIS_PREFIX` keep them from colliding. **Do not hardcode the index name.** `SCOUT_QUEUE=false` ⇒ synchronous indexing.

**Frontend.** Blade SSR. **htmx and Alpine load via CDN** in `resources/views/layouts/app.blade.php` (one `<script>` each) — NOT bundled by Vite. Only Tailwind v4 CSS goes through Vite (`@tailwindcss/vite`). htmx = server-touching interactions (search/filters); Alpine = client-only (image preview, detail gallery).

**Queue + scheduler.** `routes/console.php` registers a test scheduled task AND a **queue worker driven by the scheduler** (`Schedule::command('queue:work --stop-when-empty ...')->everyMinute()`) because Plesk has no daemon; needs `QUEUE_CONNECTION=redis` (not `sync`). Diagnostic JSON endpoints: `/infra` (drivers + last scheduler/queue run), `/infra/dispatch` (enqueues a `PingJob`), `/infra/upload-test` (writes to the public disk to verify `storage:link`).

**Auth** (`AuthController`) is hand-rolled on Laravel primitives (`Auth::attempt/login/logout` + `guest`/`auth` middleware), no Breeze. The `User` model casts `password => hashed`, so register with the plain password (never `Hash::make`, it would double-hash).

## Conventions / gotchas

- **`public/build` is intentionally tracked** (the `/public/build` line is commented out in `.gitignore`): assets are compiled on the dev machine and shipped — **Plesk never compiles**. Rebuild and commit it after frontend changes.
- **Never `git add -A`.** `temp/` holds chat-pasted screenshots (gitignored); stage explicit paths.
- **Recurring Plesk deploy actions must not run `--seed`, `scout:import`, or `key:generate`** — they duplicate the catalog / rotate the app key. Those are first-deploy-only.
- After editing `.env` on the server, run `php artisan optimize` (config is cached, edits won't take effect otherwise).
- Uploads require `php artisan storage:link` (once per deploy). Keep sensitive uploads (e.g. payment proofs) on a private disk, not the public one.
