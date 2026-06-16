# 🚀 Guía de despliegue rápido — Laravel + Blade + PostgreSQL + Meilisearch en Plesk

Playbook **probado en vivo** (lab138.littlebigpro.com) para desplegar apps Laravel como esta
en Plesk **sin estar debugueando**. Sigue el orden y copia-pega. Al final está la tabla de
síntomas→arreglo y todos los valores reales del servidor.

> ⚙️ Filosofía del stack: **Plesk NO compila nada**. Los assets (Tailwind/Vite) se compilan en el
> mini-server (`npm run build` → `public/build`, que **se versiona**). Plesk solo: clona el repo,
> instala `vendor/` con Composer, y sirve PHP-FPM desde `public/`.

---

## ✅ TL;DR — checklist de despliegue (la primera vez)

1. **Repo listo** en GitHub con `public/build` versionado (Plesk lo clona).
2. **Plesk**: desactivar Node (si lo hubo) → **PHP 8.4 + FPM** (con `pdo_pgsql`).
3. **Git** en Plesk: conectar repo, rama `main`, **deploy automático on push**.
4. **Document Root → `…/<app>/public`** (NO la raíz; NO `httpdocs`).
5. **Crear la BD PostgreSQL** en Plesk (lleva prefijo `cmurillo_`).
6. **Crear `.env`** en la raíz de la app (File Manager) — bloque listo abajo.
7. **Instalar dependencias**: módulo **PHP Composer** → botón **Instalar** (crea `vendor/`).
8. **Primer arranque** (1 comando por SSH root): genera APP_KEY + migra + siembra + indexa Meili + cachea.
9. **Verificar**: `bash deploy/verify.sh https://TU-DOMINIO`.
10. **Deploy actions seguras** (para los siguientes push, sin re-sembrar): ver §9.

> Tiempo real: ~10 min si los servicios (PG/Meili) ya están en el server.
> Meili y Redis son **compartidos** entre apps → el `.env` ya trae `SCOUT_PREFIX`/`REDIS_PREFIX` para aislar (ver §11).

---

## 0. Arquitectura del entorno (una vez por servidor)

| Pieza | Dónde | Detalle |
|---|---|---|
| Servidor | `vmi725081` (Ubuntu 22, Plesk) | acceso `root@` por SSH |
| PHP | `/opt/plesk/php/8.4/bin/php` | FPM, con `pdo_pgsql` activo |
| Composer (Plesk) | módulo **PHP Composer** (botón Instalar) | los binarios `composer`/`php` **NO están en el PATH** del deploy |
| PostgreSQL | `127.0.0.1:5432` | Plesk gestiona; **prefija** db/usuario con `cmurillo_` |
| Meilisearch | `127.0.0.1:7700` (systemd) | master key en `/etc/meilisearch.toml` |
| Usuario suscripción | `cmurillo` | dueño de los archivos; PHP-FPM corre como él |

**Regla de oro**: los comandos `artisan`/`composer` se ejecutan **como `cmurillo`, NUNCA como root**
(si los corres como root, los archivos quedan de root y PHP-FPM no puede escribir → 500 por permisos).

---

## 1. Preparar el repo (en el mini-server, antes de subir)

```bash
npm install && npm run build          # genera public/build (se VERSIONA)
git add -A && git commit -m "deploy" && git push origin main
```

`.gitignore` debe **excluir** `vendor/`, `node_modules/`, `.env` y **NO** excluir `public/build`
(en este repo `public/build` está comentado en `.gitignore` a propósito).

---

## 2. Plesk: hosting PHP

1. Si el dominio venía de Node: **Node.js → Disable Node.js**.
2. **PHP Settings → PHP 8.4** + **FPM**. Verifica que `phpinfo()` muestre **`pdo_pgsql`**
   (si falta, actívalo en el componente PHP; es el driver de PostgreSQL — error nº1 silencioso).

---

## 3. Git en Plesk

- **Websites & Domains → Git** → conecta `https://github.com/Cris9870/test` (o tu repo), rama **`main`**.
- Modo **automático** (deploy en cada push).
- ⚠️ **Las "deployment actions" SÍ se ejecutan en cada push** (ver §7). No las pongas mal o duplicarás datos.

---

## 4. Document Root = `public/`

**Hosting Settings → Document root** → apúntalo a la carpeta `public` **dentro de la raíz desplegada**.

- En lab138 la app se desplegó en la **raíz del dominio** (no en `httpdocs`):
  `…/lab138.littlebigpro.com` → docroot = `…/lab138.littlebigpro.com/public`.
- Borra cualquier `index.html` placeholder del docroot.
- ✅ Comprobación: `https://TU-DOMINIO/build/manifest.json` debe dar **200** (sirve estáticos desde `public/`).

---

## 5. Base de datos PostgreSQL

**Plesk → Databases → Add Database** (tipo **PostgreSQL**). Plesk prefija con `cmurillo_`.

Valores usados en lab138:

```
DB:        cmurillo_testlaravel
Usuario:   cmurillo_laravel
Password:  E3&$8pOx7bngjhSe
Host/Port: 127.0.0.1 : 5432
```

---

## 6. Crear el `.env` (File Manager, en la raíz de la app)

Pega esto tal cual (valores reales de lab138). `APP_KEY` se rellena en el §8:

```env
APP_NAME="Tienda Test"
APP_ENV=production
APP_DEBUG=false
APP_KEY=
APP_URL=https://lab138.littlebigpro.com
ASSET_URL=https://lab138.littlebigpro.com

APP_LOCALE=es
APP_FALLBACK_LOCALE=en

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=cmurillo_testlaravel
DB_USERNAME=cmurillo_laravel
DB_PASSWORD='E3&$8pOx7bngjhSe'

SESSION_DRIVER=file
QUEUE_CONNECTION=sync

# Cache en Redis (el redis-server es COMPARTIDO) -> aislado con REDIS_PREFIX.
# Requiere la extension phpredis en el PHP de Plesk (lab138 la tiene). Si no, usa CACHE_STORE=file.
CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PREFIX=lab138_

SCOUT_DRIVER=meilisearch
SCOUT_QUEUE=false
# El Meilisearch es COMPARTIDO entre apps -> prefijo para que el indice sea "lab138_productos"
SCOUT_PREFIX=lab138_
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=9f1211b0f1a0a2c7baf7f1b400e3bc89d8982cf9c3bcfdef181b71a3fcfa5156
```

> 🔑 **Dos claves distintas, no las confundas:**
> - **`APP_KEY`** = clave de cifrado de Laravel (cookies/sesiones). Si falta → *"No application encryption key has been specified"*. Se genera (§8).
> - **`MEILISEARCH_KEY`** = **master key de Meilisearch** (autentica a `127.0.0.1:7700`). Si está mal → *"The provided API key is invalid"*. **Fuente autoritativa**: `grep master_key /etc/meilisearch.toml`.
>
> La password de la BD va **entre comillas simples** porque tiene `&` y `$` (si no, Laravel la malinterpreta).

---

## 7. Dependencias (Composer)

`vendor/` **no está en Git** y **no se reinstala solo** en cada push (los binarios de composer no están
en el PATH del deploy). Instálalo con el **módulo de Plesk**:

- **Websites & Domains → PHP Composer → botón “Instalar”**. Crea `vendor/` con el entorno correcto.
- `vendor/` **persiste** entre despliegues (git pull no lo toca). Solo vuelve a **Instalar/Actualizar**
  cuando cambies `composer.json`/`composer.lock`. En la práctica, **casi nunca**.

> Sin `vendor/` la web da **500 con cuerpo vacío** (Laravel ni arranca).

---

## 8. Primer arranque (1 comando, SSH como root)

Genera `APP_KEY`, migra, **siembra una sola vez**, indexa Meili y cachea — **ejecutado como `cmurillo`**.
Cambia `DOMAIN` si despliegas otro dominio:

```bash
DOMAIN=lab138.littlebigpro.com
ART=$(find /var/www/vhosts/*/$DOMAIN -maxdepth 2 -name artisan -type f 2>/dev/null | head -1); APP=$(dirname "$ART"); PHP=/opt/plesk/php/8.4/bin/php; OWNER=$(stat -c '%U' "$ART"); echo ">> APP=$APP PHP=$PHP OWNER=$OWNER"; su -s /bin/bash - "$OWNER" -c "cd '$APP' && ( grep -q '^APP_KEY=base64:' .env || { sed -i '/^APP_KEY=/d' .env; echo \"APP_KEY=base64:\$(openssl rand -base64 32)\" >> .env; } ) && '$PHP' artisan migrate --force && '$PHP' artisan db:seed --force && '$PHP' artisan scout:sync-index-settings && '$PHP' artisan scout:flush 'App\\Models\\Producto' && '$PHP' artisan scout:import 'App\\Models\\Producto' && '$PHP' artisan optimize && echo PRIMER_DEPLOY_OK"
```

Deberías ver: `Application key set`/clave ya presente → migraciones `DONE` → seeder → `Imported … up to ID: 31` → `… cached` → `PRIMER_DEPLOY_OK`.

> Si más adelante **se duplicaron** los productos (p.ej. 31→62 por sembrar de más), límpialo con `migrate:fresh`:
> ```bash
> su -s /bin/bash - "$OWNER" -c "cd '$APP' && '$PHP' artisan migrate:fresh --seed --force && '$PHP' artisan scout:flush 'App\\Models\\Producto' && '$PHP' artisan scout:import 'App\\Models\\Producto' && '$PHP' artisan optimize"
> ```

---

## 9. Deploy actions para los SIGUIENTES push (Plesk → Git → acciones de despliegue)

Pega **solo esto** (con ruta completa de PHP, **SIN `--seed`, `scout:import` ni `key:generate`**):

```bash
/opt/plesk/php/8.4/bin/php artisan migrate --force && /opt/plesk/php/8.4/bin/php artisan scout:sync-index-settings && /opt/plesk/php/8.4/bin/php artisan optimize
```

Por qué exactamente esto:
- **SÍ corren en cada push** → automatizan migraciones nuevas + caché. ✅
- **`--seed` NO** → si no, **duplica el catálogo en cada push** (lo vivimos: 31→62→93). ❌
- **`scout:import` NO** → innecesario salvo que cambie el catálogo. ❌
- **`key:generate` NO** → rotaría `APP_KEY` en cada push e invalidaría sesiones. ❌
- **`composer install` NO** → se hace con el módulo Composer (§7) cuando cambian deps.

> ¿Quieres composer automático también? Es posible con la ruta completa del phar
> (`/opt/plesk/php/8.4/bin/php /usr/lib/plesk-*/composer.phar install --no-dev --optimize-autoloader && …`),
> pero corre en cada push (unos segundos extra). Opcional.

Tras cada push normal: `git pull` (automático) + estas 3 acciones. `vendor/` y `public/build` ya están.

---

## 10. Verificar (smoke test reproducible)

```bash
bash deploy/verify.sh https://lab138.littlebigpro.com
```

Comprueba HTTP 200 + PostgreSQL OK + Meilisearch OK + búsqueda con typos (`ipone`→iPhone,
`labtop`→Laptop) + faceta + detalle. Si todo va: `== TODO OK ==`.

---

## 11. ⚠️ Aislamiento multi-app (Meili y Redis son COMPARTIDOS)

En este servidor **un solo Meilisearch y un solo Redis** sirven a varias apps (al desplegar lab138
apareció un índice `anuncios` de otra app). Sin namespacing, dos apps con el mismo nombre de índice/clave
**se pisan los datos**. Cada app debe diferenciarse:

| Servicio | Mecanismo | En el `.env` | Resultado |
|---|---|---|---|
| **Meilisearch** | `SCOUT_PREFIX` | `SCOUT_PREFIX=lab138_` | índice `lab138_productos` (no choca con `anuncios`) |
| **Redis** | `REDIS_PREFIX` (+ Laravel ya separa por `APP_NAME` y usa **db 1** para caché) | `REDIS_PREFIX=lab138_` | claves `lab138_*` en db 1 |

- **Meili NO se autoprefija**: hay que poner `SCOUT_PREFIX`. Y el **modelo no debe sobreescribir
  `searchableAs()`** con un nombre fijo (el default de Scout es `config('scout.prefix').getTable()`,
  que respeta el prefijo). En este repo ya se quitó ese override.
- **Redis SÍ se autoprefija** por `APP_NAME` (prefijo por defecto `slug(APP_NAME)_database_`) y la caché
  va en la **db 1**; el `REDIS_PREFIX` explícito es un extra.
- Comandos copia-pega para aplicar/verificar ambos: **`docs/comandos-mantenimiento.md`** (secciones 1 y 5).

> Verificado en lab138: índices Meili = `anuncios` + `lab138_productos`; caché Redis `Cache::get => funciona`
> con claves `lab138_*` en db 1.

---

## 🩺 Tabla de síntomas → causa → arreglo (lo que vivimos)

| Síntoma | Causa real | Arreglo |
|---|---|---|
| **500 con cuerpo vacío** (`len=0`), PHP ejecuta | falta `vendor/` (composer no corrió) | módulo PHP Composer → **Instalar** (§7) |
| 500 con texto **"No application encryption key"** | falta/empty `APP_KEY` | generar APP_KEY (§8). Si `key:generate` dice *"already present in the environment"* = había línea `APP_KEY=` vacía → bórrala y escribe `APP_KEY=base64:…` directo |
| `key:generate`: **"No APP_KEY variable was found"** | el `.env` no tiene la línea `APP_KEY=` | añade `APP_KEY=` (o escríbela ya con valor) |
| **"The provided API key is invalid"** (Meili) | `MEILISEARCH_KEY` del `.env` ≠ master key real | copiar de `grep master_key /etc/meilisearch.toml` |
| Productos **duplicados** (31→62→93) | `migrate --seed` en deploy actions corre en cada push | quitar `--seed` de las actions (§9) + limpiar con `migrate:fresh --seed` |
| Otra app **comparte/pisa** tu índice Meili o claves Redis | servicios compartidos sin namespacing | `SCOUT_PREFIX` + `REDIS_PREFIX` (§11) |
| **"The provided API key is invalid"** tras un `config:clear` | la `MEILISEARCH_KEY` del `.env` no coincide; antes funcionaba por config cacheada | corregir la key desde `/etc/meilisearch.toml` + `optimize` |
| **Página en blanco / código fuente / 403** | Document Root no apunta a `public/` | §4 |
| Assets/CSS 404 | `ASSET_URL`/`APP_URL` mal, o `public/build` no subido | fijarlos al dominio; versionar `public/build` |
| 500 **"Permission denied"** / *failed to open stream* | `storage/`/`bootstrap/cache` no escribibles o de root | correr artisan **como `cmurillo`**, no root; `chmod -R ug+rwX storage bootstrap/cache` |
| Cambié `.env` y no surte efecto | config cacheada | `php artisan optimize` (o `config:clear`) |
| `composer: command not found` en deploy actions | binarios no están en PATH del deploy | usar el módulo Composer, o rutas completas |
| `php: command not found` en deploy actions | idem | usar `/opt/plesk/php/8.4/bin/php` (ruta absoluta) |

---

## 🧰 Plantilla de comando "correr cualquier artisan" (como el usuario correcto)

```bash
DOMAIN=lab138.littlebigpro.com
ART=$(find /var/www/vhosts/*/$DOMAIN -maxdepth 2 -name artisan -type f 2>/dev/null | head -1); APP=$(dirname "$ART"); PHP=/opt/plesk/php/8.4/bin/php; OWNER=$(stat -c '%U' "$ART")
su -s /bin/bash - "$OWNER" -c "cd '$APP' && '$PHP' artisan <LO-QUE-SEA>"
```

Ejemplos de `<LO-QUE-SEA>`: `migrate --force` · `optimize` · `scout:import 'App\Models\Producto'` ·
`config:clear` · `tinker` · `about`.

---

## 📌 Valores de referencia (lab138.littlebigpro.com)

```
Server SSH:        root@vmi725081  (o root@lab138.littlebigpro.com)
Usuario app:       cmurillo
Raíz de la app:    /var/www/vhosts/stickersllamita.com/lab138.littlebigpro.com
Document Root:     …/lab138.littlebigpro.com/public
PHP:               /opt/plesk/php/8.4/bin/php
Repo:              github.com/Cris9870/test  (rama main = Laravel)
URL:               https://lab138.littlebigpro.com

PostgreSQL:        127.0.0.1:5432
  db / user / pass: cmurillo_testlaravel / cmurillo_laravel / E3&$8pOx7bngjhSe

Meilisearch:       http://127.0.0.1:7700
  master key:      9f1211b0f1a0a2c7baf7f1b400e3bc89d8982cf9c3bcfdef181b71a3fcfa5156
  (fuente:         /etc/meilisearch.toml)
```

---

## 🔁 Resumen del ciclo de vida

| Acción | ¿Qué tocas? |
|---|---|
| Cambias código/vistas/rutas | solo `git push` (auto: pull + migrate + scout:sync + optimize) |
| Cambias migraciones | `git push` (migrate corre solo) |
| Cambias el catálogo/seeder | `git push` + correr `scout:import` una vez (§plantilla) |
| Cambias dependencias (`composer.json`) | módulo PHP Composer → **Actualizar** |
| Cambias `.env` | editar `.env` + `php artisan optimize` |
| Compilas estilos nuevos | `npm run build` en el mini-server + `git push` |
