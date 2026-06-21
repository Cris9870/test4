# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

A **Laravel 13 reverse marketplace MVP** ("Reversa") built to **validate a full stack on Plesk shared hosting**: Blade SSR + htmx + Alpine + Tailwind v4 + PostgreSQL (Eloquent) + Meilisearch (Scout) + Redis. It is deployed live at **lab139.littlebigpro.com** (repo `Cris9870/test4`). For **Peru, currency soles (S/)**. Domain identifiers are in Spanish.

**Reverse marketplace = MercadoLibre inverted:** the **buyer** publishes what they want to buy (an `Anuncio` — a buy-request: `titulo`, `descripcion`, `categoria`, `presupuesto` in S/, `ciudad`); regular people who own that item make an `Oferta` (offer to sell); the buyer **accepts** one → the anuncio becomes `cerrado`. No cart/payments (free concept). Routes: `/` (home/search), `/buscar` (htmx), `/categoria/{slug}`, `/anuncio/{id}`, `/publicar`, `/cuenta` (profile), `/admin` (hand-rolled panel), `/subir` + `/infra*` (diagnostics).

> The earlier `Producto` catalog (on lab138, repo `test`) was scaffolding to validate the stack; this app **pivoted** to Reversa (`Anuncio`/`Oferta`). There is no `Producto` model anymore.

> **Before anything deployment-related, read `docs/DESPLIEGUE-PLESK.md`** — a self-sufficient playbook for deploying NEW Laravel apps on this Plesk server (services + connection data + gotchas, incl. the accent-insensitive search fix). `deploy/verify.sh` is a live smoke test.

## Commands

Local first run:
```
composer install
cp .env.example .env && php artisan key:generate
# edit .env: DB_* (PostgreSQL) + MEILISEARCH_HOST/KEY
php artisan migrate --seed
php artisan scout:sync-index-settings && php artisan scout:import "App\Models\Anuncio"
npm install && npm run build
```

- **Run:** `php artisan serve` — or `composer dev` (serve + queue + pail + vite concurrently).
- **Tests:** `composer test` or `php artisan test` (35 tests; PHPUnit class-style on PostgreSQL `tienda_test`, with Meili forced down so the PG `ilike` fallback is exercised). Single test: `php artisan test --filter=TestName`.
- **Format:** `vendor/bin/pint`.
- **Build assets:** `npm run build` regenerates `public/build`. Run it after ANY Blade/Tailwind class or `@theme` change, then **commit `public/build`** (see Conventions).
- **Reindex search** (after changing `toSearchableArray()` or `config/scout.php`): `php artisan scout:sync-index-settings && php artisan scout:import "App\Models\Anuncio"`.

## Deployment (B1 — compile locally, ship via Git)

**The chosen model: the dev machine compiles; Plesk only pulls and fixes state. Plesk never runs Node or a build step.** Full multi-app playbook: `docs/DESPLIEGUE-PLESK.md`.

**Who does what:**
- **Dev machine** compiles assets (`npm run build` → `public/build`, which is **committed**). `vendor/` is **not** committed.
- **GitHub** is the transport (+ history / rollback). Push to `main` of `test4` → webhook → Plesk deploys.
- **Plesk pulls on push and runs the recurring deploy actions automatically** — `migrate --force && scout:sync-index-settings && optimize`. So `optimize` is **not** a manual step; it runs on every deploy. `composer install` runs only when `composer.json` changes (the native Git panel does NOT run it automatically).

**Per-change flow (the everyday loop):**
1. Edit code / views / styles.
2. `npm run build` — **only if** you touched Blade classes, Tailwind, or JS (assets). Pure PHP changes skip this.
3. Commit explicit paths (incl. `public/build` when rebuilt) and `git push`.
4. Plesk pulls → runs migrate + scout:sync + **optimize** by itself. Done.

**One-time per app (bootstrap — NEVER in the recurring deploy actions):** create the PostgreSQL DB, write `.env`, `key:generate`, `migrate --seed`, `scout:import`, `storage:link`, the Scheduler toggle, and Composer → Install. See the playbook.

**Re-run once after a deploy that adds demo data or the `busqueda` column:** `db:seed --force` + `scout:import "App\Models\Anuncio"` (seeders use `updateOrCreate`, idempotent). Otherwise just `git push`.

## Stack & services (what we have, how to reach them)

Every service runs on the Plesk host and is **shared across apps** — per-app namespacing keeps them from colliding. Full values + credentials + connection blocks live in `docs/DESPLIEGUE-PLESK.md` (§2 services, §3.e `.env`). **Never hardcode hosts/keys in code — read them from `.env`.**

- **PostgreSQL** (Eloquent) — `127.0.0.1:5432`. DBs and users carry the `cmurillo_` prefix; **usernames are truncated to 16 chars** (use the exact one Plesk shows). Needs `pdo_pgsql`. Config via `DB_*`.
- **Meilisearch** (search, via Scout) — `http://127.0.0.1:7700`, **shared**. Master key in `/etc/meilisearch.toml` → `MEILISEARCH_KEY`. Isolated per app with `SCOUT_PREFIX` → index `<prefix>anuncios`. Reached only through `app/Services/BuscadorAnuncios.php` (Scout `->raw()`), with a PostgreSQL `ilike` fallback. `SCOUT_QUEUE=false` ⇒ synchronous indexing.
- **Redis** — `127.0.0.1:6379` via `phpredis`; cache on **db 1**. Isolated with `REDIS_PREFIX` **and** `APP_NAME` (Laravel keys cache by `APP_NAME` → keep it unique per app). Backs cache (`CACHE_STORE=redis`) + queue (`QUEUE_CONNECTION=redis`).
- **SMTP** (mail) — a Plesk mailbox per domain. `MAIL_*` in `.env`; mailbox password is an **external** secret → server `.env` only. Smoke test: `php artisan mail:test <to>`.
- **Queue + Scheduler** — Plesk has no daemon, so a scheduler-driven worker in `routes/console.php` runs `queue:work --stop-when-empty` every minute. Requires the **Scheduler toggle** on + `QUEUE_CONNECTION=redis`. Diagnostics: `/infra`, `/infra/dispatch`, `/infra/upload-test`.

## Architecture (the parts that span files)

**Domain model.** `Anuncio` (buy-request, belongs to a buyer `User` + a `Categoria`, has many `Oferta`, `estado` `abierto`/`cerrado`), `Oferta` (belongs to an anuncio + seller `User`, `estado` `pendiente`/`aceptada`/`rechazada`), `Categoria`, and `User` (extended with a boolean `es_admin`). Offers flow: publish → offer → buyer **accepts** one (transaction: that oferta `aceptada`, siblings `rechazada`, anuncio `cerrado`) — see `OfertaController@aceptar`.

**Search is the core.** `app/Services/BuscadorAnuncios.php` is the single search entry point, used by both the home (`HomeController`) and the htmx endpoint (`BuscarController`):
- Queries Meilisearch via Scout `->raw()` to obtain hits + `processingTimeMs` + `facetDistribution`.
- Runs **two** Meili queries: filtered hits, and facet counts *without* the category filter (disjunctive facets, so the user can switch categories).
- On any Meili exception it **falls back to PostgreSQL** (Eloquent `ilike`) so the page never dies.
- Returns a uniform view-model rendered by `resources/views/partials/resultados.blade.php`.
- `Anuncio::shouldBeSearchable()` returns `estado === 'abierto'` → closed anuncios drop out of the index automatically.

**Accent-insensitive search.** The server's Meilisearch does NOT fold diacritics, so a normalized `busqueda` column (no accents, lowercased) is kept by `Anuncio`'s `saving()` hook, added to `searchableAttributes`, and used by the PG fallback; `BuscadorAnuncios::fold()` normalizes the query the same way. ⚠️ Seeders must NOT use `WithoutModelEvents` (it kills the hook) — `DatabaseSeeder` uses `Anuncio::disableSearchSyncing()` instead. Full recipe: playbook §13.1.

**htmx partial swap.** `/buscar` returns ONLY `partials/resultados` (grid + facet chips + a hidden `#categoria-actual`), which htmx swaps into `#resultados`. The search input and chips carry each other's state via `hx-include`. `partials/card.blade.php` uses **array access** (`$p['id'|'titulo'|'categoria'|'presupuesto'|'imagen_url']`, plus `data_get($p,'ciudad'|'ofertas_count'|'estado')`) so it renders both Eloquent models (home/fallback) and Meili hit arrays (search) — `toSearchableArray()` includes `imagen_url` + `ofertas_count` so cards render from Meili hits with no DB round-trip.

**Multi-tenant search isolation.** The `Anuncio` model **deliberately does not override `searchableAs()`**, so Scout's default `config('scout.prefix').getTable()` applies → the index is `SCOUT_PREFIX + 'anuncios'`. Meili and Redis are shared; `SCOUT_PREFIX`/`REDIS_PREFIX`/`APP_NAME` keep apps from colliding. **Do not hardcode the index name.**

**Frontend.** Blade SSR. The "Reversa" green/amber/cream design is ported into Tailwind v4 (`@theme` tokens in `resources/css/app.css` + the bespoke component CSS). **htmx and Alpine load via CDN** in `resources/views/layouts/app.blade.php` (one `<script>` each) — NOT bundled by Vite. Only the CSS goes through Vite (`@tailwindcss/vite`). htmx = server-touching interactions (search/filters); Alpine = client-only (image preview). ⚠️ **Gotcha:** a component class name must not collide with a Tailwind utility (a class `invert` triggered `filter:invert` and inverted colors); and the link reset belongs in `@layer base` (`a { color: inherit }`) so component classes (`.btn-pub`, `.h-link`, unlayered) win.

**Admin (hand-rolled, no packages).** Boolean `es_admin` on `User` + `EnsureUserIsAdmin` middleware (alias `admin` in `bootstrap/app.php`) + a `Route::middleware(['auth','admin'])->prefix('admin')` group → `AdminController` (dashboard + moderate anuncios/ofertas/usuarios/categorias) with Blade views under `resources/views/admin/`. Served at the `/admin` subfolder (no extra subdomain).

**Queue + scheduler.** `routes/console.php` registers a scheduler ping AND a **queue worker driven by the scheduler** (`Schedule::command('queue:work --stop-when-empty ...')->everyMinute()`) because Plesk has no daemon; needs `QUEUE_CONNECTION=redis` (not `sync`). Diagnostic JSON: `/infra`, `/infra/dispatch` (enqueues a `PingJob`), `/infra/upload-test` (verifies `storage:link`).

**Auth** (`AuthController`) is hand-rolled on Laravel primitives (`Auth::attempt/login/logout` + `guest`/`auth` middleware), no Breeze. The `User` model casts `password => hashed`, so register with the plain password (never `Hash::make`, it would double-hash).

## Conventions / gotchas

- **`public/build` is intentionally tracked** (the `/public/build` line is commented out in `.gitignore`): assets compile on the dev machine and ship — **Plesk never compiles**. Rebuild and commit after any frontend/`@theme` change.
- **Never `git add -A`.** `temp/` holds chat-pasted screenshots (gitignored); the `diseño/` folder is the source mockup (kept untracked). Stage explicit paths.
- **Recurring Plesk deploy actions must not run `--seed`, `scout:import`, or `key:generate`** — they duplicate demo data / rotate the app key. Those are first-run-only (seeders are idempotent via `updateOrCreate`, but keep the rule).
- **Money is `S/` (soles); locale `es`, timezone `America/Lima`** (set in `config/app.php`).
- After editing `.env` on the server, run `php artisan optimize` (config is cached, edits won't take effect otherwise).
- Uploads require `php artisan storage:link` (once per deploy); anuncio/oferta images go to the `public` disk. Keep sensitive uploads on the private disk.
