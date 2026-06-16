# Tienda Test — Laravel + Blade + Meilisearch (validación de stack sobre Plesk)

Web **mínima** tipo e-commerce (solo **catálogo + búsqueda**) cuyo único objetivo es
**validar el stack** Laravel + Blade + htmx + Alpine + Meilisearch sobre **Plesk (PHP-FPM)**
y el flujo de despliegue **"compilo assets en el mini-server y subo a Plesk ya compilados"**.

> No es una tienda real: **sin carrito, sin pagos, sin auth, sin panel admin, sin colas/cron/Redis**.

> **Estado: ✅ VALIDADO EN VIVO** en `https://lab138.littlebigpro.com` (Plesk, PHP-FPM 8.4) — PostgreSQL OK, Meilisearch OK, 31 productos, búsqueda con typos (`ipone`→iPhone, `labtop`→Laptop), facetas y detalle con Alpine. Smoke test reproducible: `bash deploy/verify.sh`.

## Stack

| Pieza | Tecnología |
|------|------------|
| Framework | **Laravel 13** (PHP 8.2+; probado en 8.4) |
| Vistas | **Blade** (SSR, HTML renderizado en servidor) |
| Interactividad servidor | **htmx 2** (búsqueda y filtros sin recargar) — por **CDN** |
| Interactividad cliente | **Alpine.js 3** (galería del detalle) — por **CDN** |
| Estilos | **Tailwind CSS v4** vía **Vite** (compilado en el mini-server → `public/build`) |
| Base de datos | **PostgreSQL** vía **Eloquent** (sin SQL crudo) |
| Buscador | **Meilisearch** vía **Laravel Scout** (driver `meilisearch`) |

htmx y Alpine se cargan con **una sola etiqueta `<script>` cada uno** (CDN), **no** pasan por Vite
(ver `resources/views/layouts/app.blade.php`).

## Qué incluye

- **Modelo `Producto`** (`nombre, descripcion, categoria, precio, stock, imagen_url`) + migración + seeder con **31 productos** variados (electrónica, hogar, deportes, moda, libros, juguetes, belleza).
- **`/`** (SSR): barra de búsqueda + grilla de tarjetas + **panel de estado** (PostgreSQL OK/FALLO con query real Eloquent, Meilisearch OK/FALLO con health-check; cada uno en su `try/catch`) + **timestamp del servidor** en cada request.
- **`/buscar`** (htmx): devuelve un **parcial** Blade con la grilla y **reemplaza solo `#resultados`** (debounce 300 ms). Usa Scout → Meilisearch.
  - **Tolerancia a typos**: `ipone`→ *iPhone*, `labtop`→ *Laptop* (typo-tolerance de Meili + **synonyms** configurados).
  - **Faceta por categoría** (`filterableAttributes`) que también se actualiza vía htmx.
  - Muestra el **`processingTimeMs`** que devuelve Meili en cada query.
- **`/producto/{id}`** (SSR, URL indexable): detalle del producto.
- **Microinteracción Alpine** (galería de miniaturas en el detalle) que **no toca el servidor**.
- **Tolerancia a fallos**: si Meilisearch cae, la búsqueda **degrada a PostgreSQL** (Eloquent) y la página sigue en pie.

---

## 1) Correr en LOCAL (mini-server)

Requisitos: **PHP 8.2+** (con `pdo_pgsql`), **Composer**, **Node + npm**, un **PostgreSQL** y un **Meilisearch** accesibles.

### 1.1 Servicios locales (PostgreSQL + Meilisearch)

Si no los tienes corriendo en el mini-server:

```bash
# PostgreSQL (Debian/Ubuntu) + driver PHP
sudo apt-get install -y postgresql php8.4-pgsql

# Crear base y usuario (UTF8)
sudo -u postgres psql -c "CREATE ROLE tienda LOGIN PASSWORD 'tienda';"
sudo -u postgres psql -c "CREATE DATABASE tienda OWNER tienda ENCODING 'UTF8' TEMPLATE template0 LC_COLLATE 'C' LC_CTYPE 'C';"

# Meilisearch (binario)
sudo curl -L https://github.com/meilisearch/meilisearch/releases/download/v1.47.0/meilisearch-linux-amd64 -o /usr/local/bin/meilisearch
sudo chmod +x /usr/local/bin/meilisearch
# Arrancar (en otra terminal o como servicio). Usa una master key:
MEILI_MASTER_KEY=unaMasterKeyLocal MEILI_NO_ANALYTICS=true \
  meilisearch --http-addr 127.0.0.1:7700 --db-path /var/lib/meili-dev/data.ms
```

### 1.2 La aplicación

```bash
# 1. Dependencias PHP
composer install

# 2. Variables de entorno
cp .env.example .env
php artisan key:generate         # si APP_KEY está vacía

# 3. Configurar .env:  DB_* de PostgreSQL local, MEILISEARCH_HOST y MEILISEARCH_KEY locales
#    DB_CONNECTION=pgsql  DB_HOST=127.0.0.1  DB_PORT=5432  DB_DATABASE=tienda  DB_USERNAME=tienda  DB_PASSWORD=...
#    SCOUT_DRIVER=meilisearch  SCOUT_QUEUE=false  MEILISEARCH_HOST=http://127.0.0.1:7700  MEILISEARCH_KEY=...

# 4. Migrar + sembrar (31 productos)
php artisan migrate --seed

# 5. Empujar ajustes del índice (faceta, synonyms, typo-tolerance) e indexar en Meili
php artisan scout:sync-index-settings
php artisan scout:import "App\Models\Producto"

# 6. Compilar assets (Tailwind/Vite) -> genera public/build
npm install
npm run build

# 7. Servir
php artisan serve            # http://127.0.0.1:8000
```

### 1.3 Comprobar que funciona ANTES de hablar de Plesk

Abre `http://127.0.0.1:8000` y verifica:

- El **panel de estado** muestra **PostgreSQL OK** y **Meilisearch OK** + el timestamp del servidor.
- Se ven las **31 tarjetas** de producto.
- En el buscador, escribe **`ipone`** → aparece **iPhone**; **`labtop`** → aparece **Laptop** (typo tolerante).
- Aparece el badge **`⚡ Meilisearch · N ms`** (el `processingTimeMs`).
- Haz clic en una **categoría** (chip) → la grilla se filtra **sin recargar**.
- Entra a un producto (`/producto/1`) y prueba la **galería Alpine** (clic en miniaturas cambia la imagen principal, sin peticiones).

> Verificado en este mini-server: Home 200 (PG OK + Meili OK + 31 tarjetas), `ipone`→1 (iPhone),
> `labtop`→3 (Laptops), faceta `Hogar`→6, `ipone`+`Hogar`→0, detalle 200, `/producto/99999`→404.

### Comando para reindexar (memorízalo)

```bash
php artisan scout:import "App\Models\Producto"      # reindexa todo el catálogo
php artisan scout:flush  "App\Models\Producto"      # vacía el índice (si hace falta)
php artisan scout:sync-index-settings               # re-aplica facetas/synonyms/typo-tolerance
```

`SCOUT_QUEUE=false` ⇒ la **indexación es SÍNCRONA**: cada `create/update/delete` se refleja en Meili
en el acto, **sin worker de cola** (no necesitas `queue:work` en este test).

---

## 2) Despliegue en Plesk (checklist)

> Aquí es donde Laravel suele fallar en Plesk. Sigue los 6 puntos **en orden**.

### 2.1 ⚠️ Document Root = `public/` (error nº1)

WordPress sirve desde la raíz; **Laravel sirve desde `public/`**. Si dejas el docroot en la raíz del
proyecto verás el código fuente o un 403/500 y `@vite`/rutas no funcionarán.

- Plesk → **Websites & Domains → (tu dominio) → Hosting Settings → Document root**.
- Apúntalo a la carpeta `public` del proyecto, p.ej.:
  `/var/www/vhosts/tu-dominio/httpdocs/public` (o `.../tu-app/public` si subes el proyecto a un subdirectorio).
- Si Plesk dejó un **`index.html` placeholder** dentro del docroot, **bórralo** (si no, nginx lo sirve y nunca llega a Laravel).

### 2.2 `vendor/` NO se sube — lo regenera Plesk + acciones de despliegue Git

`vendor/` y `node_modules/` están en `.gitignore`. Plesk trae **Composer integrado**.
Configura las **acciones de despliegue** (Git → *Deployment actions*). Cada línea corre en un shell
separado, así que **encadena con `&&`** lo que dependa de un mismo entorno.

**Primer despliegue (one-time)** — incluye `key:generate`, `--seed` y `scout:import`:

```bash
composer install --no-dev --optimize-autoloader \
  && php artisan key:generate --force \
  && php artisan migrate --seed --force \
  && php artisan scout:sync-index-settings \
  && php artisan scout:import "App\Models\Producto" \
  && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

**Despliegues siguientes (recurrentes)** — **SIN `--seed` ni `scout:import`** (re-sembrar en
cada push **duplicaría** los productos; reindexar completo es innecesario si los datos no cambian):

```bash
composer install --no-dev --optimize-autoloader \
  && php artisan migrate --force \
  && php artisan scout:sync-index-settings \
  && php artisan config:cache && php artisan route:cache && php artisan view:cache
```

> `key:generate` solo en el primer despliegue (o fija `APP_KEY` a mano en `.env`). **No** lo dejes en
> las acciones recurrentes: cambiaría la clave en cada push e invalidaría sesiones/cookies cifradas.
> `--force` es obligatorio en producción (entorno no interactivo). `scout:sync-index-settings` es
> idempotente; `scout:import` solo cuando cambie el catálogo.

> **PATH/chroot**: si las acciones fallan con `php: command not found` o `composer: not found`, usa las
> rutas completas de Plesk, p.ej. `/opt/plesk/php/8.3/bin/php artisan ...` y
> `/opt/plesk/php/8.3/bin/php /usr/lib/plesk-9.0/composer.phar install ...`. Asegura también que el
> acceso SSH del dominio sea `/bin/bash` (no *chrooted*).

### 2.3 `.env` (NO se sube al repo)

`.env` está en `.gitignore`. Créalo en el servidor (Plesk → **File Manager** o por SSH) con, al menos:

```env
APP_NAME="Tienda Test"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...            # generada (o php artisan key:generate)
APP_URL=https://tu-dominio
ASSET_URL=https://tu-dominio  # para que las rutas de @vite/build resuelvan absolutas

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=...               # OJO: Plesk PREFIJA con el slug del cliente, p.ej. cmurillo_tienda
DB_USERNAME=...               #      y el usuario, p.ej. cmurillo_tiendauser
DB_PASSWORD=...

SCOUT_DRIVER=meilisearch
SCOUT_QUEUE=false
MEILISEARCH_HOST=http://127.0.0.1:7700   # Meili corre como servicio en el server
MEILISEARCH_KEY=...                       # master key de Meilisearch del server
```

> **Gotcha Plesk**: la base y el usuario de PostgreSQL suelen llevar el **prefijo del cliente**
> (p.ej. `cmurillo_tienda`, `cmurillo_tiendauser`), no el nombre "pelado".
> Tras editar el `.env`, vuelve a cachear config (2.6) o **reinicia la app**.

### 2.4 Permisos: `storage/` y `bootstrap/cache/` escribibles

**Síntoma**: error 500 / *"Permission denied"* / *"failed to open stream: Permission denied"* /
*"The stream or file ... could not be opened"* al primer request o al cachear.

**Arreglo** (usuario PHP del dominio, normalmente el de la suscripción):

```bash
chmod -R ug+rwX storage bootstrap/cache
# si el dueño no es el usuario PHP:
# chown -R <usuario-suscripcion>:psacln storage bootstrap/cache
```

### 2.5 Assets: `public/build` se sube ya compilado (Plesk NO compila)

- Los assets se compilan en el **mini-server** con `npm run build` y se **versionan**: por eso
  `/public/build` está **deliberadamente fuera de `.gitignore`** (ver comentario en el archivo).
- Plesk **no necesita Node**: `@vite([...])` lee `public/build/manifest.json` y sirve los CSS/JS ya compilados.
- Para que las URLs de los assets resuelvan bien, fija **`APP_URL`** y **`ASSET_URL`** al dominio real
  (`https://tu-dominio`). Si ves los assets en 404, casi siempre es `ASSET_URL`/docroot mal puestos.
- Comprueba en el server que existe `public/build/manifest.json` tras el deploy.

### 2.6 Cachés tras desplegar

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
# atajo equivalente:
php artisan optimize
```

Repite `config:cache` (o `php artisan config:clear`) **cada vez que cambies el `.env`** en el server;
si no, Laravel seguirá usando la config cacheada anterior.

### 2.7 ⚠️ Procedimiento REAL usado en lab138 (lo que de verdad pasó)

Aprendizaje clave: en este Plesk las *additional deployment actions* **SÍ se ejecutan en cada push/deploy**, pero `composer`/`php` "pelados" **no están en el PATH** del shell de deploy. Al principio parecía que "no corrían" porque **fallaban en el primer comando** (`composer install` / `php artisan key:generate` no encontrados, o sin `APP_KEY`/`vendor`) y el `&&` cortaba toda la cadena. Una vez resueltos `vendor/`, `APP_KEY` y la ruta de PHP, las actions corren enteras — y por eso **`migrate --seed` en las actions DUPLICA productos en cada push** (lo vimos: 31→62→93). El flujo fiable:

1. **Dependencias** → módulo **PHP Composer** de Plesk (botón **Instalar**). Corre con el entorno correcto y crea `vendor/` (sin él: 500 con cuerpo vacío).
2. **Crear `.env`** (File Manager) en la raíz de la app. La `MEILISEARCH_KEY` debe ser la **master key real** del server: `grep master_key /etc/meilisearch.toml`.
3. **Document Root → `…/public`** (la app se desplegó en la raíz del dominio, no en `httpdocs`).
4. **Pasos artisan** con `deploy/deploy.sh`, ejecutado **como el usuario de la suscripción** (no root):

   ```bash
   # primer despliegue (siembra + indexa). APP=raiz de la app
   su -s /bin/bash - cmurillo -c "bash $APP/deploy/deploy.sh --seed"
   # despliegues siguientes (sin re-sembrar, para no duplicar)
   su -s /bin/bash - cmurillo -c "bash $APP/deploy/deploy.sh"
   ```

   El script detecta el PHP de Plesk (`/opt/plesk/php/8.4/bin/php`), genera `APP_KEY` si falta, migra, sincroniza ajustes de índice y cachea. Ejecutarlo **como root rompe permisos** (PHP-FPM corre como el usuario de la suscripción).
5. **Verificar**: `bash deploy/verify.sh https://lab138.littlebigpro.com`.

**Deployment actions seguras (recurrentes)** — déjalas así en Plesk para que cada push auto-despliegue **sin re-sembrar**:

```bash
/opt/plesk/php/8.4/bin/php artisan migrate --force && /opt/plesk/php/8.4/bin/php artisan scout:sync-index-settings && /opt/plesk/php/8.4/bin/php artisan optimize
```

> **NO** pongas `migrate --seed`, `scout:import` ni `key:generate` en las actions recurrentes: el seed duplica el catálogo en cada push, `scout:import` es innecesario y `key:generate --force` rotaría `APP_KEY` cada vez. La siembra/indexado inicial se hizo una vez con `deploy/deploy.sh --seed` (o `migrate:fresh --seed` + `scout:flush`/`scout:import` para limpiar duplicados).

> Gotchas vividos: `key:generate` falla con *"No APP_KEY variable was found"* (falta la línea) o *"APP_KEY is already present in the environment"* (línea `APP_KEY=` vacía) → el script escribe `APP_KEY=base64:…` directo. `migrate --seed` repetido **duplica** productos → re-sembrar solo con `migrate:fresh --seed` + `scout:flush`/`scout:import`.

---

## Estructura relevante

```
app/Models/Producto.php                  # Eloquent + Scout (Searchable, toSearchableArray)
app/Services/BuscadorProductos.php        # Meili (Scout) con fallback a PostgreSQL
app/Http/Controllers/HomeController.php   # home + panel de estado (PG/Meili en try/catch)
app/Http/Controllers/BuscarController.php # endpoint htmx (parcial)
app/Http/Controllers/ProductoController.php
config/scout.php                          # index-settings: filterable, synonyms, typoTolerance
database/migrations/..._create_productos_table.php
database/seeders/ProductoSeeder.php       # 31 productos
resources/views/
  layouts/app.blade.php                   # htmx + Alpine por CDN, @vite(css)
  home.blade.php  producto.blade.php
  partials/{estado,resultados,card}.blade.php
routes/web.php                            # /  /buscar  /producto/{id}
```

## Troubleshooting rápido

| Síntoma | Causa probable | Arreglo |
|--------|----------------|---------|
| Página en blanco / código fuente visible | Document root no apunta a `public/` | Punto 2.1 |
| `Vite manifest not found` | falta `public/build` | `npm run build` y versiónalo (2.5) |
| Assets 404 | `ASSET_URL`/`APP_URL` mal | Fíjalos al dominio (2.5) |
| 500 *Permission denied* | `storage/`/`bootstrap/cache` no escribibles | Punto 2.4 |
| Cambié `.env` y no surte efecto | config cacheada | `php artisan config:cache` (2.6) o reiniciar app |
| Buscador vacío / Meili FALLO | índice sin poblar o Meili caído | `scout:import`; revisar `MEILISEARCH_HOST/KEY` |
| Búsqueda funciona pero sin facetas/typos | faltó sincronizar settings | `php artisan scout:sync-index-settings` |
