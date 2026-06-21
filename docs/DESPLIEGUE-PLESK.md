# 🚀 Playbook — desplegar apps Laravel en Plesk (multi-app, mismo servidor)

Guía **autosuficiente y probada en vivo** (lab138 + lab139.littlebigpro.com) para lanzar **cualquier
app Laravel nueva** en un subdominio/dominio nuevo del **mismo servidor Plesk**, reutilizando los
servicios ya instalados (PostgreSQL, Meilisearch, Redis, SMTP).

> **Si eres una IA desplegando una app nueva**: lee §0 (TL;DR), §1 (modelo), §2 (servicios y cómo
> conectarte) y §3 (pasos por app). Todo lo necesario —hosts, claves, prefijos, comandos— está aquí.
> Las recetas de código reutilizable (búsqueda, tildes, infra, auth, uploads) están en §13–§15.

---

## ⚙️ El modelo (resumen en 3 frases)

1. **Plesk NO compila nada.** Tailwind/Vite se compilan en tu máquina (`npm run build` → `public/build`,
   que **se versiona en git**). Plesk solo clona el repo, instala `vendor/` con Composer y sirve PHP-FPM desde `public/`.
2. **Transporte = Git + webhook.** Haces `git push`; un webhook dispara en Plesk: *pull* + *deploy actions*
   (`migrate --force && scout:sync-index-settings && optimize`). Sin SSH para el día a día.
3. **Servicios compartidos** (Meili, Redis, PG, SMTP) entre todas las apps del server → **aislamiento por
   prefijo** (`SCOUT_PREFIX`, `REDIS_PREFIX`, prefijo `cmurillo_` en BD). Nunca hardcodees nombres de índice.

> Este modelo se llamó "B1" durante las pruebas. Alternativa descartada: compilar en Plesk (exige Node ≥22
> en el server + un paso que puede fallar en cada deploy). **No lo uses**; versiona `public/build`.

---

## A. Crear la app y el repo (de dónde sale el código)

Este playbook asume un **repo Laravel ya construido** con los building blocks (§ "Building blocks"). Para
una app nueva, dos caminos:

**Opción 1 — Plantilla (RECOMENDADO): parte del repo de referencia `test4` (app "Reversa").**
```bash
git clone https://github.com/Cris9870/test4.git <app> && cd <app>
rm -rf .git && git init                              # repo limpio
gh repo create <tu-usuario>/<app> --private --source=. --remote=origin   # o crea el repo en la UI de GitHub
npm install && npm run build                         # genera public/build (se VERSIONA)
git add -A && git commit -m "init <app>" && git push -u origin main
```
Ya trae listo: infra (scheduler/cola + `/infra*`), auth a medida + rol admin, uploads, búsqueda
Meili+fallback PG, **búsqueda sin tildes** (§13.1), tests. Ajusta el dominio (Modelos/seeders/vistas) a tu caso.

**Opción 2 — Laravel limpio + copiar building blocks.**
```bash
composer create-project laravel/laravel <app>
```
Copia del repo de referencia: `routes/console.php`, `app/Http/Controllers/InfraController.php`,
`app/Jobs/PingJob.php`, el patrón `BuscadorXxx` (§13), `deploy/`, y deja `public/build` versionado en `.gitignore`.

**Sobre el repo en GitHub:** puede ser **privado**. Necesitas `git push` autenticado (PAT o SSH). Plesk
hará el *pull* por la URL del repo: para repo **privado**, en el panel Git de Plesk se añade una **deploy
key** (Plesk genera una clave pública SSH → la pegas en GitHub → repo → Settings → Deploy keys) o se usa la
URL con token. **Requisito**: PHP **8.3+** (el server corre **8.4**, recomendado).

---

## 0. ✅ TL;DR — checklist para una app NUEVA (subdominio nuevo)

Reemplaza `<app>` por un prefijo corto único (p.ej. `lab140`) y `<DOMINIO>` por el subdominio.

1. **Repo en GitHub** con `public/build` versionado y `.gitignore` excluyendo `vendor/ node_modules/ .env`.
2. **Plesk → crear el subdominio**; PHP **8.4 + FPM** (con `pdo_pgsql`); si venía de Node, **Disable Node.js**.
3. **Git (panel nativo de Plesk)**: conecta el repo, **ruta de despliegue = la RAÍZ del dominio** (NO `/public`),
   rama `main`, activa el **webhook** y pon las *additional deployment actions* (§3.b).
4. **Document Root → `…/<DOMINIO>/public`** (la `public/` que trae el repo). Borra cualquier `index.html`.
5. **Crear BD PostgreSQL** en Plesk (prefijo `cmurillo_`, password **alfanumérica**).
6. **`.env`** (File Manager o Toolkit→Editar) — bloque en §3.d, con `SCOUT_PREFIX=<app>_` y `REDIS_PREFIX=<app>_`.
7. **Composer install** (Toolkit→Composer, o shell) → crea `vendor/`.
8. **Primer arranque** (Artisan): `key:generate --force`, `migrate --seed --force`, `scout:sync-index-settings`,
   `scout:import App\Models\<Modelo>`, `storage:link`, `optimize` (§3.f).
9. **Scheduler**: toggle **Tareas programadas = Activado** (mueve el worker de cola).
10. **Verificar** (§5) y dejar las deploy actions recurrentes ya puestas.

> ⚠️ Los 3 errores que más nos costaron, evítalos de entrada: **(a)** ruta de despliegue a `/public`
> (anida `public/public`); **(b)** usuario PG truncado a 16 chars; **(c)** `APP_KEY` vacío = 500 en TODA
> ruta web. Detalle en §3 y en la tabla de síntomas.

---

## 1. Filosofía y preparación del repo (mini-server)

```bash
npm install && npm run build          # genera public/build (se VERSIONA)
# commit con rutas explícitas (NUNCA 'git add -A' si hay temp/ con capturas)
git add public/build <tus-archivos> && git commit -m "deploy" && git push origin main
```

`.gitignore` **debe**: excluir `vendor/`, `node_modules/`, `.env`; y **NO** excluir `public/build`
(la línea `/public/build` va comentada). Cualquier cambio de Blade/Tailwind/CSS ⇒ `npm run build` + commit de `public/build`,
o el sitio sale **sin estilos**.

---

## 2. 🔌 Servicios compartidos del servidor y CÓMO CONECTARTE

Todo corre en el **mismo host** por loopback (`127.0.0.1`) y se comparte entre apps. El aislamiento es
por **prefijo** (clave: dos apps sin prefijo se pisan los datos).

| Servicio | Host:puerto | Cómo conectar (en `.env`) | Aislamiento | Dónde está la clave |
|---|---|---|---|---|
| **PostgreSQL** | `127.0.0.1:5432` | `DB_CONNECTION=pgsql` + `DB_*` | BD/usuario con prefijo `cmurillo_` | la creas tú en Plesk → Databases |
| **Meilisearch** | `http://127.0.0.1:7700` | `SCOUT_DRIVER=meilisearch` + `MEILISEARCH_HOST/KEY` | `SCOUT_PREFIX=<app>_` → índice `<app>_<tabla>` | master key en `/etc/meilisearch.toml` (`grep master_key`) |
| **Redis** | `127.0.0.1:6379` | `CACHE_STORE=redis` + `QUEUE_CONNECTION=redis` + `REDIS_*` (`REDIS_CLIENT=phpredis`) | `REDIS_PREFIX=<app>_` (+ Laravel separa por `APP_NAME`; caché en db 1) | sin auth en loopback |
| **SMTP** | el propio dominio | `MAIL_*` (§15-bis) | buzón por dominio | password del buzón (Plesk → Correo) |

**Valores reales del server (loopback, internos):**
```
PostgreSQL : 127.0.0.1:5432   (driver pdo_pgsql; confírmalo en phpinfo)
Meilisearch: http://127.0.0.1:7700
             master key = 9f1211b0f1a0a2c7baf7f1b400e3bc89d8982cf9c3bcfdef181b71a3fcfa5156
             (si falla "The provided API key is invalid": grep master_key /etc/meilisearch.toml)
Redis      : 127.0.0.1:6379   (phpredis disponible; caché en db 1)
```

**Reglas de oro del aislamiento:**
- **Meili NO se autoprefija** → pon `SCOUT_PREFIX=<app>_` y **NO sobreescribas `searchableAs()`** en el
  modelo (el default `config('scout.prefix').getTable()` respeta el prefijo). Índice resultante: `<app>_<tabla>`.
- **Redis sí se autoprefija** por `APP_NAME` (+ caché en db 1); `REDIS_PREFIX` es un extra.
- **PostgreSQL**: cada app su propia BD (no compartas BD entre apps).

---

## 3. Pasos por app (subdominio nuevo)

### 3.a — Crear el subdominio + DNS + SSL + PHP
- **Plesk → Sitios web y dominios → Añadir subdominio** (bajo la suscripción/dominio padre del server). El
  DNS de un subdominio en el mismo server suele auto-crearse; con DNS externo, apunta un A/CNAME al server y espera propagación.
- **SSL/TLS**: Plesk → (el subdominio) → **Certificados SSL/TLS → Instalar Let's Encrypt** (marca el dominio;
  `www` opcional). **Sin SSL, `https://<DOMINIO>` no sirve TLS y `verify.sh` (que usa https) falla.**
- Si venía de Node: **Node.js → Disable Node.js**.
- **PHP Settings → PHP 8.4 + FPM** (mínimo soportado 8.3). Verifica `pdo_pgsql` activo (error nº1 silencioso si falta).

### 3.b — Git por el **panel nativo de Plesk** (Sitios web y dominios → Git)
En lab139 el **Laravel Toolkit no mostró la opción de GitHub**; el panel **Git nativo** de Plesk hace el
mismo trabajo y es el camino fiable. **Reparto de herramientas:** *Git/deploy* = panel **Git nativo**;
*Composer y Artisan* = pestañas del **Laravel Toolkit** (siguen disponibles aunque el Git lo lleve el panel
nativo). Si tampoco aparece la pestaña Artisan, usa SSH (Apéndice A).

> ### 🔴 ORDEN DEL PRIMER DEPLOY (evita el huevo-y-gallina)
> Las *deploy actions* corren `migrate/optimize`, que **fallan sin `vendor/`**. Por eso, **en el primer
> despliegue NO actives el webhook ni pongas las deploy actions todavía**:
> 1. Conecta el repo (abajo) en modo **Manual**, **sin** additional deployment actions.
> 2. Pulsa **Desplegar** (primer *pull* del código).
> 3. **Composer → Install** (crea `vendor/`) + crea BD + escribe `.env` (§3.d, §3.e).
> 4. **Primer arranque** Artisan (§3.f): `key:generate`, `migrate --seed`, `scout:*`, `storage:link`, `optimize`.
> 5. **SOLO AHORA** añade las *additional deployment actions* y activa el **webhook** (deploy automático).
>
> A partir de aquí, cada `git push` despliega solo.

**Conectar el repo:**
- **Repositorio remoto**: la URL del repo (HTTPS, o SSH con deploy key si es privado — §A). Rama `main`.
- **⚠️ RUTA DE DESPLIEGUE = la RAÍZ del dominio** (`…/<DOMINIO>`), **NUNCA `…/public`**. Si la pones en
  `/public`, el repo (que trae su propia `public/`) queda **anidado** (`public/public/index.php`) → `/index.php`
  da 404 y el Toolkit no detecta la app. *(Si ya pasó: Git → Eliminar repositorio + reconectar a la raíz;
  NO borres el subdominio, perderías SSL/DNS.)*

**Webhook + deploy actions (paso 5 del orden de arriba):**
- **Webhook**: copia la URL que da Plesk → GitHub → repo → Settings → Webhooks → Add webhook (Payload URL =
  esa, Content type `application/json`, evento *push*). Deploy automático en cada push.
- **Additional deployment actions** (lo que corre tras cada pull) — **ruta completa de PHP**:
  ```
  /opt/plesk/php/8.4/bin/php artisan migrate --force && /opt/plesk/php/8.4/bin/php artisan scout:sync-index-settings && /opt/plesk/php/8.4/bin/php artisan optimize
  ```
  ⚠️ **NO** pongas aquí `--seed`, `scout:import` ni `key:generate` (duplican datos / rotan la clave; son de §3.f).
  ⚠️ El panel Git nativo **NO corre `composer install` solo** (a diferencia del Toolkit) → reinstala `vendor/`
  a mano (Composer → Install) cuando cambie `composer.json`.

### 3.c — Document Root
**Hosting Settings → Document root = `…/<DOMINIO>/public`**. Borra cualquier `index.html` placeholder.
✅ Check: `https://<DOMINIO>/build/manifest.json` debe dar **200**.

### 3.d — Base de datos PostgreSQL (Plesk → Databases → Add Database, tipo PostgreSQL)
- Prefijo `cmurillo_`. **Password SOLO alfanumérica** (sin `# ! $ &`: rompen el parseo del `.env`).
- **⚠️ Plesk trunca el nombre de usuario a 16 caracteres.** Si pides `cmurillo_milarga`, el usuario real
  puede quedar truncado. **Lee el nombre EXACTO** en **Plesk → Databases → (tu BD) → sección Users** y
  cópialo literal a `DB_USERNAME` (un typo o asumir el nombre completo → `password authentication failed`).
  El nombre de la BASE no se trunca.

### 3.e — `.env` (File Manager → crear `.env`, o Toolkit → Editar)
Plantilla lista (reemplaza `<app>`, `<DOMINIO>` y los datos de BD; deja `APP_KEY=` vacío, lo llena §3.f):
```env
APP_NAME="<NombreApp>"
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=https://<DOMINIO>
ASSET_URL=https://<DOMINIO>

APP_LOCALE=es
APP_FALLBACK_LOCALE=en
# (El repo de referencia ya fija timezone 'America/Lima' en config/app.php.)

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cmurillo_<bd>
DB_USERNAME=cmurillo_<user-EXACTO-del-panel>
DB_PASSWORD=tu_password_alfanumerica

SESSION_DRIVER=file
QUEUE_CONNECTION=redis

CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PREFIX=<app>_

SCOUT_DRIVER=meilisearch
SCOUT_QUEUE=false
SCOUT_PREFIX=<app>_
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=9f1211b0f1a0a2c7baf7f1b400e3bc89d8982cf9c3bcfdef181b71a3fcfa5156

# SMTP (opcional; solo si la app envía correo). Bloque completo y cómo crear el buzón: §15-bis.
# MAIL_MAILER=smtp  MAIL_SCHEME=smtps  MAIL_HOST=<DOMINIO>  MAIL_PORT=465 …
```
> - **`APP_NAME` debe ser ÚNICO por app**: además de `SCOUT_PREFIX`/`REDIS_PREFIX`, Laravel aísla la caché
>   y las claves Redis usando `APP_NAME` (`Str::slug(APP_NAME).'-cache-'`). Dos apps con el mismo `APP_NAME`
>   se pisarían la caché. Pon un nombre distinto en cada una.
> - **NO copies `.env.example`** del repo al server: trae `QUEUE_CONNECTION=sync` y `CACHE_STORE=file`
>   (defaults locales) que **contradicen** este bloque (redis). Usa **este** bloque.
> - `SESSION_DRIVER=file` evita tener que migrar la tabla `sessions`. La master key de Meili de arriba es
>   un **valor de ejemplo** (puede haber rotado): si `scout:import` da *"API key is invalid"*, obtén la real
>   con `grep master_key /etc/meilisearch.toml` (acceso root SSH **puntual** permitido para el setup; la
>   regla "nunca root" aplica a correr *artisan*, no a leer un archivo de config).
> - Si tu password de BD tuviera símbolos, entrecomíllala con `'comillas simples'`. Tras cualquier edición
>   del `.env`: **`php artisan optimize`** (la config se cachea; si no, no surte efecto).

### 3.f — Composer + Primer arranque (Toolkit → pestañas Composer y Artisan)
El **Laravel Toolkit** es opcional pero cómodo: corre como `cmurillo` (sin problemas de permisos) y sin SSH.
Si el Toolkit entra en bucle ("artisan pide .env / el editor de .env pide artisan"), es porque la app aún
no está booteada (falta `vendor/` y/o `.env`): **crea el `.env` por File Manager** y **vendor/** por la
pestaña Composer o shell — el Toolkit re-detecta la app sola.

1. **Composer → Install** (crea `vendor/`; sin él la web da **500 con cuerpo vacío**).
2. **Artisan** (uno a uno, **SIN comillas** — el Toolkit las toma literales):
   ```
   key:generate --force
   migrate --seed --force
   scout:sync-index-settings
   scout:import App\Models\<Modelo>
   storage:link
   optimize
   ```
   - **`<Modelo>`** = la clase de `app/Models/` que usa el trait `Searchable` (mira también las claves de
     `config/scout.php`). En el repo de referencia es `Anuncio` → `scout:import App\Models\Anuncio`.
   - **`migrate --seed` y `scout:import` son CONDICIONALES**: `--seed` solo si la app trae seeders de datos
     demo; `scout:import` solo si la app usa Meili (hay un modelo `Searchable`). Si no, omítelos.
   - **APP_KEY**: si `key:generate` dice *"already present in the environment"* y la web da **500 en TODA
     ruta web pero Artisan funciona** = no hay clave válida. Workaround: `key:generate --show` → copia el
     `base64:...` y **pégalo a mano** en la línea `APP_KEY=` del `.env` → `optimize`. (El 500-solo-en-web
     ocurre porque los middleware de sesión/CSRF necesitan APP_KEY; Artisan no.)
   - **`scout:import`**: en el Toolkit va **sin comillas** (`scout:import App\Models\Foo`); con comillas da
     *"Model not found"*. (Por SSH sí van comillas.)

### 3.g — Scheduler + Cola (toggle)
- **Toggle "Tareas programadas" = Activado** → Plesk crea el cron `schedule:run` cada minuto.
- **Cola**: el toggle "Cola" del Toolkit suele no activarse (quirk de UI). **No lo necesitas**: el repo
  trae un worker movido por el scheduler en `routes/console.php`:
  ```php
  Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
      ->everyMinute()->withoutOverlapping(5);
  ```
  Con el scheduler activo y `QUEUE_CONNECTION=redis`, procesa lo encolado cada minuto (latencia ~1 min,
  100% fiable). Certificado end-to-end en lab139.

---

## 4. 🔁 Ciclo de vida (qué tocas en cada cambio)

| Cambias… | Acción |
|---|---|
| Código / vistas / rutas / migraciones | `npm run build` (si tocaste assets) → `git push` → el webhook corre migrate+scout:sync+optimize |
| Estilos/Tailwind | `npm run build` + commit `public/build` + `git push` |
| Datos demo / seeder | `git push`, luego **una vez** en Artisan: `db:seed --force` + `scout:import App\Models\<Modelo>` |
| Dependencias (`composer.json`) | Toolkit → Composer → Install (el panel Git nativo no lo hace solo) |
| `.env` | editar + Artisan → `optimize` |

---

## 5. ✅ Verificación

**HTTP smoke test** (desde cualquier sitio con acceso a la URL):
```bash
bash deploy/verify.sh https://<DOMINIO>
```
Adapta los *needles* de `deploy/verify.sh` a tu app (busca 200 + "PostgreSQL"/"available" + rutas reales).

**Infra (cola + scheduler)** — endpoints de diagnóstico del repo de referencia:
- `GET /infra` → JSON con `queue_connection`, `cache_store`, `scheduler_last_run`, `queue_last_job`.
- `GET /infra/dispatch` → encola un `PingJob`. **Scheduler OK** si `scheduler_last_run` avanza cada minuto;
  **Cola OK** si tras dispatch el `queue_last_job` toma el token (en ~1 min).
- `GET /infra/upload-test` → valida `storage:link` + disco public.

**Verificar el deploy automático (truco del marcador):** haz un cambio invisible (un comentario HTML en el
layout), `git push`, y comprueba por HTTP que aparece en vivo; luego revierte. Confirma webhook→pull→publicado.

---

## 6. 📌 Valores de referencia

```
Usuario app (PHP-FPM): cmurillo   (todo el Toolkit/artisan corre como él; NUNCA root → 500 por permisos)
PHP:                   /opt/plesk/php/8.4/bin/php
Apps de ejemplo:       github.com/Cris9870/test  (lab138)  ·  github.com/Cris9870/test4 (lab139, "Reversa")
Raíz de app (patrón):  /var/www/vhosts/<suscripcion>/<DOMINIO>   (deploy aquí)
Document Root:         …/<DOMINIO>/public

PostgreSQL : 127.0.0.1:5432  (prefijo cmurillo_; usuario truncado a 16 chars)
Meilisearch: http://127.0.0.1:7700  (master key en /etc/meilisearch.toml = 9f1211b0…fcfa5156)
Redis      : 127.0.0.1:6379  (phpredis; caché db 1)
```

---

## 🩺 Tabla de síntomas → causa → arreglo (todo lo que vivimos)

| Síntoma | Causa | Arreglo |
|---|---|---|
| **500 con cuerpo vacío**, PHP ejecuta | falta `vendor/` | Composer → **Install** |
| `/index.php` da **404** (PHP responde, X-Powered-By presente) | ruta de despliegue a `/public` → repo anidado `public/public` | reconectar Git a la **raíz del dominio** (§3.b); NO borrar el subdominio |
| **500 en TODA ruta web**, pero Artisan OK | falta `APP_KEY` válido (web usa CSRF/sesión, artisan no) | `key:generate --show` → pegar `base64:` en `.env` → `optimize` |
| `key:generate`: *"already present in the environment"* | clave inválida/vacía en el entorno | el workaround `--show` de arriba |
| **"The provided API key is invalid"** (Meili) | `MEILISEARCH_KEY` ≠ master key | `grep master_key /etc/meilisearch.toml` → corrige + `optimize` |
| BD: **`password authentication failed`** | usuario PG truncado a 16 chars, o typo, o password con símbolos sin comillas | usa el user EXACTO del panel; password alfanumérica; `optimize:clear` |
| Cambié `.env`/datos y "no surte efecto" | config cacheada | `optimize:clear` luego `optimize` |
| Otra app **pisa** tu índice/claves | servicios compartidos sin prefijo | `SCOUT_PREFIX` + `REDIS_PREFIX` (§2) |
| `scout:import`: **"Model not found"** (Toolkit) | la pestaña Artisan toma las comillas literales | **sin comillas**: `scout:import App\Models\Foo` |
| Datos **duplicados** tras varios push | `--seed`/`scout:import` en deploy actions recurrentes | quítalos (van en §3.f); `migrate:fresh --seed` para limpiar |
| Toggle **Cola** no se activa | quirk de UI del Toolkit | usar el **worker por scheduler** (§3.g) |
| Imagen subida da **404** | falta symlink `public/storage` | `php artisan storage:link` |
| Toolkit en **bucle** (.env↔artisan) | app sin botear (sin `vendor/`/`.env`) | crear `.env` por **File Manager** + `vendor/` por Composer; el Toolkit re-detecta |
| Página en blanco / código fuente / 403 | Document Root no apunta a `public/` | §3.c |
| Assets/CSS 404 o sin estilos | `public/build` sin versionar, o `ASSET_URL`/`APP_URL` mal, o falta `npm run build` | versiona `public/build`; fija URLs al dominio |
| Texto con **color invertido/raro** (azul donde debería ir ámbar, blanco sobre blanco) | una clase CSS choca con una **utilidad Tailwind** (p.ej. `invert`=filter, `grayscale`, `blur`) | renombra la clase del componente (ver §14) |
| 500 *Permission denied* | artisan corrido como **root** | usar el Toolkit (corre como `cmurillo`) o `su - cmurillo` |

---

## 🧩 Building blocks reutilizables (copia del repo de referencia `test4`)

El repo `Cris9870/test4` (app "Reversa") trae piezas listas para reusar en una app nueva:
- **Infra/diagnóstico**: `routes/console.php` (scheduler + worker de cola), `InfraController`, `PingJob`,
  endpoints `/infra*`. Cópialos tal cual: validan cola+scheduler+uploads en cualquier app.
- **Búsqueda** (§13), **tildes** (§13.1), **Auth a medida** (§15), **Uploads** (§14-bis), **Mail** (§15-bis).
- `deploy/verify.sh` (smoke test) y `deploy/deploy.sh` (despliegue manual por SSH, fallback).

---

## 13. 🔎 Búsqueda con Meilisearch + fallback PostgreSQL (patrón)

Un único servicio `BuscadorXxx` (ej. `app/Services/BuscadorAnuncios.php`) es la entrada de búsqueda:
- Consulta Meili vía Scout `->raw()` → hits + `processingTimeMs` + `facetDistribution`.
- Hace **dos** queries: hits filtrados, y conteo de facetas **sin** el filtro de categoría (facetas disjuntas).
- Ante **cualquier** excepción de Meili, **degrada a PostgreSQL** (`ilike`) → la página nunca se cae.
- Devuelve un view-model uniforme (`items,total,processingTimeMs,facets,q,categoria,fuente,error`) que
  consumen los parciales Blade. El modelo es `Searchable` y **no** sobreescribe `searchableAs()` (respeta `SCOUT_PREFIX`).
- `config/scout.php` lleva por **nombre de tabla** los `searchableAttributes`/`filterableAttributes`/
  `sortableAttributes`/`synonyms`/`typoTolerance`. `SCOUT_QUEUE=false` ⇒ indexado síncrono.

### 13.1 — ⭐ Búsqueda INSENSIBLE A TILDES (corrección importante)
El Meilisearch de este server **no pliega diacríticos** por defecto → buscar `sofa` (sin tilde) no
encuentra `sofá`. Solución **a nivel app** (no depende del server ni de la extensión `unaccent` de PG):

> **Prerrequisito:** el modelo ya implementa el patrón §13 (`use Searchable` + un `toSearchableArray()`).
> Aquí solo **AÑADES** la clave `busqueda` a ese array existente. Requiere la extensión **`intl`** de PHP
> (o el polyfill de Symfony) para que `ascii()` translitere bien los acentos — confírmala en `phpinfo()`.

1. **Migración** — columna normalizada:
   ```php
   $table->text('busqueda')->nullable()->after('categoria');  // 'after' asume que 'categoria' ya existe;
   // en PostgreSQL la posición de columnas es cosmética (no afecta).
   ```
2. **Modelo** — un hook que la rellena al guardar (texto sin acentos, minúsculas):
   ```php
   protected static function booted(): void {
       static::saving(function (self $m): void {
           $m->busqueda = \Illuminate\Support\Str::of($m->titulo.' '.$m->descripcion.' '.$m->categoria)
               ->ascii()->lower()->squish()->value();
       });
   }
   ```
   y en `toSearchableArray()` añade `'busqueda' => $this->busqueda`.
3. **`config/scout.php`** — añade `'busqueda'` a `searchableAttributes` de esa tabla.
4. **Servicio de búsqueda** — normaliza la query **una vez** y úsala en todas las consultas:
   - Helper: `private function fold(string $s): string { return Str::of($s)->ascii()->lower()->squish()->value(); }`
   - Meili: `$qf = $this->fold($q);` y pasa **el mismo `$qf`** a la búsqueda de **hits Y** a la de **facetas**.
   - Fallback PG: `->where('busqueda', 'ilike', "%{$qf}%")` (la columna ya viene normalizada).
5. **⚠️ Gotcha del seeder**: `WithoutModelEvents` (común en `DatabaseSeeder`) **apaga el hook `saving()`**
   → `busqueda` queda vacía. **Quita `use WithoutModelEvents`** del `DatabaseSeeder` y pon
   `Modelo::disableSearchSyncing();` al inicio de su `run()`, **ANTES** del `$this->call([...])` que invoca
   al seeder del modelo (corre el hook **sin** indexar a Meili durante el sembrado).
6. **Tras desplegar** cambios de este campo: `migrate` añade la columna (auto), pero hay que **`db:seed --force`**
   + **`scout:import App\Models\<Modelo>`** (reindexa). ⚠️ `db:seed` solo rellena `busqueda` si tu seeder
   **re-guarda cada fila** (`updateOrCreate`/`save`), NO con `insert()` masivo. Para una tabla ya poblada sin
   re-seed, rellénala una vez por tinker: `App\Models\<Modelo>::query()->each->save();`.

✅ **Verifica**: tras `scout:import`, busca un término **sin tilde** que exista acentuado (ej. `sofa` → debe
encontrar `sofá`). Certificado en lab139: `sofa`/`café`/`bici` encuentran sus artículos acentuados.

> **Imágenes de demo temáticas**: para que las fotos coincidan con el contenido, usa
> `https://loremflickr.com/600/450/{keyword}?lock=N` (por palabra clave) en vez de `picsum.photos` (paisajes random).

---

## 14. 🎨 Frontend: Blade + htmx + Alpine + Tailwind v4 (gotchas B1)

- **htmx y Alpine van por CDN** en el layout (un `<script>` cada uno), **NO** por Vite. Solo el CSS
  (Tailwind v4 vía `@tailwindcss/vite`) pasa por Vite. htmx = interacciones que tocan el server
  (buscar/filtrar/chat-por-polling); Alpine = solo cliente (preview de imagen, galería).
- **⚠️ Colisión de nombres con utilidades Tailwind**: una clase de componente que se llame igual que una
  utilidad de Tailwind (p.ej. `invert`, `grayscale`, `blur`, `block`, `flex`…) **aplica esa utilidad**. Vivido:
  una clase `invert` puso `filter:invert(100%)` → invirtió los colores (ámbar→azul, blanco→oscuro). **Renombra**
  la clase del componente (p.ej. `invert`→`util-claim`).
- **⚠️ Reset de enlaces y especificidad**: si pones `.algo a { color: inherit }` (espec. 0,1,1) vence a las
  clases de botón/enlace `.btn` (0,1,0) → texto que hereda el color equivocado (p.ej. blanco sobre blanco).
  Pon el reset de `a` en `@layer base { a { color: inherit; text-decoration: none } }` → las clases de
  componente (sin capa) lo vencen. (El CSS portado va **sin capa**; el preflight de Tailwind va en `@layer base`.)
- **Disciplina B1**: cualquier cambio de Blade/Tailwind/`@theme` ⇒ `npm run build` + commit de `public/build`.

### 14-bis. Uploads
- `php artisan storage:link` (**una vez por app**) crea `public/storage → storage/app/public`. Sin él: **404**.
- Guarda en el disco `public`: `$request->file('img')->store('carpeta','public')`; sirve con
  `Storage::disk('public')->url($path)`. Datos sensibles → disco `local` (privado, `storage/app/private`).
- Para archivos grandes sube `upload_max_filesize`/`post_max_size` en PHP Settings.

---

## 15. 🔐 Auth a medida (sin paquetes) + 15-bis Email/SMTP

**Auth** con primitivas del core (`Auth::attempt/login/logout` + sesiones + middleware `guest`/`auth`): da
registro/login/logout/ruta protegida sin Breeze. El modelo `User` castea `'password' => 'hashed'` → en el
registro pasa la contraseña **en claro** (NO `Hash::make`, doblaría el hash). **Rol admin** sin paquetes:
columna boolean `es_admin` + middleware `EnsureUserIsAdmin` (alias `admin` en `bootstrap/app.php`) + grupo
`Route::middleware(['auth','admin'])->prefix('admin')`. Un panel admin en subcarpeta `/admin` evita crear otro subdominio.

**SMTP**: primero **crea el buzón** en **Plesk → Correo → Crear dirección de correo** (ahí defines su
contraseña). Luego en `.env`:
```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtps            # 465=SSL (smtps) · 587=STARTTLS (smtp). Laravel 11+ usa MAIL_SCHEME, no MAIL_ENCRYPTION
MAIL_HOST=<DOMINIO>
MAIL_PORT=465
MAIL_USERNAME=buzon@<DOMINIO>
MAIL_PASSWORD='<password-del-buzon>'   # credencial EXTERNA → NO al repo público
MAIL_FROM_ADDRESS=buzon@<DOMINIO>      # = el buzón autenticado (el SMTP rechaza si no coincide)
MAIL_FROM_NAME="<NombreApp>"
```
Tras editar: `optimize` + `mail:test tucorreo@dominio.com` (sin comillas). Certificado en lab139.

---

## 🧰 Apéndice A — Método manual por SSH (fallback)

Cuando el Toolkit/panel falle. **Ejecuta como `cmurillo`, nunca como root.** Plantilla:
```bash
DOMAIN=<DOMINIO>
ART=$(find /var/www/vhosts/*/$DOMAIN -maxdepth 2 -name artisan -type f 2>/dev/null | head -1)
APP=$(dirname "$ART"); OWNER=$(stat -c '%U' "$ART")
su -s /bin/bash - "$OWNER" -c "cd '$APP' && /opt/plesk/php/8.4/bin/php artisan <COMANDO>"
```
`deploy/deploy.sh --seed` (en el repo) encadena el primer arranque. Más bloques copia-pega:
`docs/comandos-mantenimiento.md`.

> Truco anti-pega: los bloques `su -c "…"` se desformatean al pegar desde un chat → usa un **heredoc a
> `/tmp/script.sh`** o copia desde el botón de GitHub.
