# 🚀 Guía de despliegue — Laravel en Plesk con el **Laravel Toolkit**

Playbook **probado en vivo** (lab138.littlebigpro.com) para desplegar apps Laravel
(Blade + PostgreSQL + Meilisearch + Redis) en Plesk **sin debuguear**.

> **Esta versión usa el Laravel Toolkit de Plesk como herramienta principal**: hace casi todo
> desde el panel (Artisan, Composer, `.env`, Scheduler, Cola, Despliegue) **como el usuario de la
> suscripción (`cmurillo`)** → permisos correctos y **sin SSH** para la mayoría de pasos.
> El método manual por terminal queda como **Apéndice A** (fallback fiable).

> ⚙️ Filosofía del stack: **Plesk NO compila nada**. Tailwind/Vite se compilan en el mini-server
> (`npm run build` → `public/build`, que **se versiona**). Plesk clona el repo, instala `vendor/`
> con Composer y sirve PHP-FPM desde `public/`.

---

## 0. El Laravel Toolkit = tu centro de mando

Plesk → **Sitios web y dominios → (tu dominio) → Laravel Toolkit**. Lo que ofrece:

| Control | Para qué |
|---|---|
| Pestaña **Artisan** | ejecutar cualquier `php artisan …` desde el panel |
| Pestaña **Composer** | `composer install/update` (crea/actualiza `vendor/`) |
| Pestaña **Despliegue** | deploy por Git + acciones de despliegue |
| Pestaña **Cola** | opciones del worker de cola (timeout, nº trabajos…) |
| Pestaña **Node.js** | no la necesitas (assets precompilados) |
| Botón **Editar** (Variables de entorno) | editor del `.env` |
| Toggle **Tareas programadas** | activa el Scheduler de Laravel (`schedule:run` cada minuto) |
| Toggle **Cola** | activa el worker de cola (requiere paquete, ver §9) |
| Toggle **Modo de mantenimiento** | `artisan down/up` |

> 🔑 **Regla de oro**: todo lo del Toolkit corre como **`cmurillo`** (no root) → cero problemas de
> permisos. Si en algún momento usas el método manual (Apéndice A), **ejecuta artisan como `cmurillo`,
> nunca como root** (si no, los archivos quedan de root y PHP-FPM da 500 por permisos).

---

## ✅ TL;DR — checklist (la primera vez)

1. **Repo** en GitHub con `public/build` versionado.
2. **Plesk**: PHP **8.4 + FPM** (con `pdo_pgsql`); si venía de Node, **Disable Node.js**.
3. **Git/Despliegue** (Toolkit): repo, rama `main`, **deploy automático on push**.
4. **Document Root → `…/<app>/public`** (NO la raíz, NO `httpdocs`).
5. **Crear BD PostgreSQL** en Plesk (lleva prefijo `cmurillo_`).
6. **`.env`** → Toolkit **Editar** (bloque en §6).
7. **Composer** → Toolkit pestaña **Composer → Install** (crea `vendor/`).
8. **Primer arranque** → Toolkit pestaña **Artisan**: `key:generate`, `migrate --seed`, `scout:*`, `optimize`.
9. **Scheduler + Cola** → toggles (§9).
10. **Verificar** (§11) y dejar las **deploy actions seguras** (§10).

> Meili y Redis son **compartidos** entre apps → el `.env` ya trae `SCOUT_PREFIX`/`REDIS_PREFIX` (ver §12).

---

## 1. Preparar el repo (mini-server)

```bash
npm install && npm run build          # genera public/build (se VERSIONA)
git add -A && git commit -m "deploy" && git push origin main
```

`.gitignore` debe **excluir** `vendor/`, `node_modules/`, `.env` y **NO** excluir `public/build`.

---

## 2. Plesk: hosting PHP

- Si venía de Node: **Node.js → Disable Node.js**.
- **PHP Settings → PHP 8.4 + FPM**. Confirma `pdo_pgsql` en `phpinfo()` (driver de PostgreSQL — error nº1 silencioso).

---

## 3. Despliegue por Git (Toolkit → pestaña **Despliegue**)

Esta pestaña tiene los **pasos de despliegue integrados** (mucho mejor que el método manual):

- **URL de Webhook**: cópiala y pégala en GitHub → repo → **Settings → Webhooks → Add webhook**
  (Payload URL = la webhook, Content type `application/json`, evento *push*). Habilita el deploy en cada push.
- **Modo**: **Automático** (deploy on push) o **Manual** (botón "Desplegar").
- **Pasos de despliegue** (checkboxes) — configuración recomendada:
  - ✅ 1 Activar mantenimiento · ✅ 2 Recuperar código · ✅ 3 Desplegar Git
  - ✅ **4 Instalar dependencias `composer.json`** ← **el Toolkit corre `composer install` SOLO** (¡adiós al módulo manual!)
  - ☐ **5 Instalar `package.json`** → **DESMÁRCALO** (los assets van precompilados; no compilamos en Plesk)
  - ✅ **6 Ejecutar script de despliegue** → en **"Editar script"** pon los artisan recurrentes:
    ```
    php artisan migrate --force && php artisan scout:sync-index-settings && php artisan optimize
    ```
  - ✅ 7 Desactivar mantenimiento
- ⚠️ En el script **NO** pongas `--seed`, `scout:import` ni `key:generate` (duplican el catálogo / rotan la clave). Esos son de **primer arranque** (§8).

> 💡 **Corrección importante**: con la pestaña **Despliegue** del Toolkit, `composer install` **SÍ es
> automático** (paso 4) y el "script de despliegue" (paso 6) hace de *deploy actions*, todo como `cmurillo`.
> Esto reemplaza al **§7 (módulo Composer manual)** y al **§10 (acciones de Git crudas)**, que quedan solo
> como alternativa si no usas el Toolkit.

---

## 4. Document Root = `public/`

**Hosting Settings → Document root** → la carpeta `public` **dentro de la raíz desplegada**
(p.ej. `…/lab138.littlebigpro.com/public`). Borra cualquier `index.html` placeholder.
✅ Check: `https://TU-DOMINIO/build/manifest.json` debe dar **200**.

---

## 5. Base de datos PostgreSQL

**Plesk → Databases → Add Database** (tipo **PostgreSQL**; prefijo `cmurillo_`).

> 💡 **Usa una contraseña SOLO alfanumérica** (sin `# ! $ &`). En el `.env` esos caracteres rompen el
> parseo (`#` inicia comentario, `$` interpola, …); si los usas, hay que entrecomillar con `'comillas
> simples'`. Y revisa bien el **nombre de usuario** (Plesk lo prefija `cmurillo_`): un typo da
> `password authentication failed`.

Valores de lab138:

```
DB:        cmurillo_testlaravel
Usuario:   cmurillo_laravel
Password:  E3&$8pOx7bngjhSe
Host/Port: 127.0.0.1 : 5432
```

---

## 6. `.env` (Toolkit → **Editar**)

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
# Cola en Redis (necesario para que el worker procese; sync NO usa worker)
QUEUE_CONNECTION=redis

# Cache en Redis (COMPARTIDO) -> aislado con REDIS_PREFIX. Requiere phpredis (lab138 lo tiene).
CACHE_STORE=redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PREFIX=lab138_

SCOUT_DRIVER=meilisearch
SCOUT_QUEUE=false
# Meilisearch COMPARTIDO -> prefijo: el indice sera "lab138_productos"
SCOUT_PREFIX=lab138_
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=9f1211b0f1a0a2c7baf7f1b400e3bc89d8982cf9c3bcfdef181b71a3fcfa5156
```

> 🔑 **Dos claves distintas**: `APP_KEY` (cifrado de Laravel; falta → *"No application encryption key"*) y
> `MEILISEARCH_KEY` (master key de Meili; mal → *"The provided API key is invalid"*, sácala de
> `grep master_key /etc/meilisearch.toml`). La password de BD va **entre comillas simples** (tiene `&` y `$`).
>
> ⚠️ Tras editar el `.env`, **re-cachea** (Artisan → `optimize`) o no surte efecto (config cacheada).

---

## 7. Dependencias (Toolkit → pestaña **Composer** → Install)

Crea `vendor/` con el entorno correcto. `vendor/` **persiste** entre despliegues (git pull no lo toca);
solo vuelve a **Install/Update** cuando cambie `composer.json` (en la práctica, casi nunca).

> Sin `vendor/` → la web da **500 con cuerpo vacío** (Laravel ni arranca).
> `composer install` **NO** corre solo en el deploy → por eso se usa esta pestaña.

---

## 8. Primer arranque (Toolkit → pestaña **Artisan**)

Ejecuta estos comandos **uno a uno** desde la pestaña Artisan (corren como `cmurillo`, sin SSH).
⚠️ En la pestaña Artisan **NO se ponen comillas** en los argumentos (el Toolkit las toma literales):

```
key:generate --force          # solo si APP_KEY está vacío
migrate --seed --force        # crea tablas + siembra (UNA sola vez)
scout:sync-index-settings     # facetas/synonyms/typo del indice
scout:import App\Models\Producto      # SIN comillas (el Toolkit las toma literales)
optimize                      # cachea config/rutas/vistas
```

> ⚠️ Dos detalles que nos mordieron:
> - **`scout:import "App\Models\Producto"`** con comillas → *"Model [...] not found"*. En el Toolkit va **sin comillas**: `scout:import App\Models\Producto`. (Por SSH/terminal sí van comillas.)
> - `--seed` y `scout:import` son de **primer arranque**. NO los pongas en las deploy actions
>   recurrentes (§10) o duplicarás el catálogo en cada push.

---

## 9. Scheduler + Cola (toggles del Toolkit)

### Scheduler → toggle **Tareas programadas = Activado**
Plesk crea el cron de `schedule:run` (cada minuto). Listo. ✅ Verificado en lab138.

### Cola → requiere paquete + toggle
1. Añade al `composer.json` el paquete de integración: **`plesk/ext-laravel-integration`**
   (`composer require plesk/ext-laravel-integration`, luego Toolkit → Composer → Install).
2. Toggle **Tareas programadas** activo (la cola depende de él).
3. Pestaña **Cola** → "Detener trabajo cuando esté vacío" **desmarcado** (worker persistente), tiempos a **0**.
4. Toggle **Cola = Activado**.

> ⚠️ **Quirk vivido en lab138**: el toggle **Cola** no se dejó activar ni con el paquete instalado ni con
> el scheduler activo (mensajes que cambiaban solos: *"instale el paquete"* → *"active el trabajo de cola"*).
> Parece un **bug de la UI de Plesk**, no de la app.
>
> **Fallback fiable (el que usamos): worker movido por el Scheduler.** Ya está en el repo,
> `routes/console.php`:
> ```php
> Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')
>     ->everyMinute()->withoutOverlapping(5);
> ```
> Con el toggle **Tareas programadas** activo y `QUEUE_CONNECTION=redis`, el scheduler ejecuta el worker
> cada minuto: procesa lo encolado y sale. **Latencia ~1 min** (vs. instantáneo del toggle Cola), pero
> 100% fiable y sin depender del toggle. ✅ Certificado en lab138 (job procesado end-to-end).

---

## 10. Deploy actions recurrentes (Despliegue/Git → acciones)

Para los SIGUIENTES push (con ruta completa de PHP, **sin** seed/import/key:generate):

```bash
/opt/plesk/php/8.4/bin/php artisan migrate --force && /opt/plesk/php/8.4/bin/php artisan scout:sync-index-settings && /opt/plesk/php/8.4/bin/php artisan optimize
```

- `--seed`/`scout:import`/`key:generate` **NO** aquí (duplican datos / rotan la clave).
- `composer install` **NO** aquí → se hace por la pestaña Composer cuando cambian deps.

---

## 11. Verificar

```bash
bash deploy/verify.sh https://lab138.littlebigpro.com
```

Comprueba 200 + PostgreSQL OK + Meilisearch OK + typos (`ipone`→iPhone, `labtop`→Laptop) + faceta + detalle.

**Cola + Scheduler** (endpoints de diagnóstico de ESTA app de test):
- `GET /infra` → JSON con `queue_connection`, `cache_store`, `scheduler_last_run`, `queue_last_job`.
- `GET /infra/dispatch` → encola un `PingJob` (duerme 3s). Con `redis` la respuesta es **instantánea**
  (job a Redis) y `queue_last_job` aparece cuando el **worker** lo procesa.
- **Scheduler OK** si `scheduler_last_run` se actualiza cada minuto. **Cola OK** si tras `/infra/dispatch`
  el `queue_last_job` toma el token despachado.

---

## 12. ⚠️ Aislamiento multi-app (Meili y Redis son COMPARTIDOS)

Un solo Meilisearch y un solo Redis sirven a varias apps (en lab138 apareció un índice `anuncios` de otra app).
Sin namespacing, dos apps con el mismo índice/clave **se pisan los datos**.

| Servicio | Mecanismo | `.env` | Resultado |
|---|---|---|---|
| **Meilisearch** | `SCOUT_PREFIX` | `SCOUT_PREFIX=lab138_` | índice `lab138_productos` |
| **Redis** | `REDIS_PREFIX` (+ Laravel ya separa por `APP_NAME`; caché en **db 1**) | `REDIS_PREFIX=lab138_` | claves `lab138_*` |

- **Meili NO se autoprefija** → pon `SCOUT_PREFIX` y **no sobreescribas `searchableAs()`** con un nombre
  fijo (el default `config('scout.prefix').getTable()` respeta el prefijo). En este repo ya se quitó el override.
- **Redis SÍ se autoprefija** por `APP_NAME` y la caché va en **db 1**; el `REDIS_PREFIX` es un extra.

---

## 13. Email / SMTP (validado en lab139)

Crea un buzón en Plesk (**Correo → Crear dirección de correo**) y configura en `.env` (Toolkit → Editar):

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtps                                 # SSL (puerto 465). Para STARTTLS (587): MAIL_SCHEME=smtp
MAIL_HOST=lab139.littlebigpro.com
MAIL_PORT=465
MAIL_USERNAME=test@lab139.littlebigpro.com
MAIL_PASSWORD='<contraseña-del-buzon>'            # credencial EXTERNA -> NO la subas al repo público
MAIL_FROM_ADDRESS=test@lab139.littlebigpro.com    # = el buzón autenticado
MAIL_FROM_NAME="Tienda Lab139"
```

- **Puerto 465 → `MAIL_SCHEME=smtps`** (SSL implícito). Puerto **587 → `MAIL_SCHEME=smtp`** (STARTTLS).
  Laravel 11+ usa **`MAIL_SCHEME`**, no `MAIL_ENCRYPTION`.
- El **`FROM` debe ser el buzón autenticado** (los SMTP rechazan si no coincide).
- Tras editar el `.env`: **Artisan → `optimize`**, luego **Artisan → `mail:test tucorreo@dominio.com`** (sin comillas).
  `OK: enviado … sin excepcion` + el correo llega → ✅ **SMTP certificado** (probado en lab139).
- El comando de prueba está en el repo: `app/Console/Commands/TestMail.php` (`mail:test {to}`).

> 🔒 La password del buzón es una credencial **externa** (el SMTP es accesible desde Internet): a
> diferencia de las claves de loopback (PG/Meili/Redis), **no conviene** ponerla en un repo público
> (riesgo de abuso/spam). Va solo en el `.env` del server.

---

## 14. Subida de archivos / uploads (validado en lab139)

- **`php artisan storage:link`** (Artisan tab, **una vez por app**) crea el symlink
  `public/storage → storage/app/public`. **Sin él, los archivos subidos dan 404.**
- Guarda en el disco `public`: `$request->file('imagen')->store('uploads', 'public')`; sirve con
  `Storage::disk('public')->url($path)` → `https://dominio/storage/uploads/…`.
- `storage/app/public` lo escribe `cmurillo` (usuario de PHP-FPM) → sin problemas de permisos.
  Para archivos grandes, sube `upload_max_filesize` / `post_max_size` en **PHP Settings**.
- Demo en el repo: **`/subir`** (formulario con preview por Alpine + galería) y **`/infra/upload-test`**
  (diagnóstico JSON del symlink). Ver `UploadController`.
- ✅ Certificado en lab139: POST real con CSRF (sesión `file`) → guardado → servido `200 image/png`
  (de paso valida que **sesiones + CSRF** funcionan en Plesk).

---

## 🩺 Tabla de síntomas → causa → arreglo (lo que vivimos)

| Síntoma | Causa | Arreglo |
|---|---|---|
| **500 con cuerpo vacío**, PHP ejecuta | falta `vendor/` | Toolkit → Composer → **Install** |
| 500 **"No application encryption key"** | falta/empty `APP_KEY` | Artisan → `key:generate --force`. Si dice *"already present in the environment"* = línea `APP_KEY=` vacía → bórrala/pon valor |
| `key:generate`: *"No APP_KEY variable was found"* | el `.env` no tiene la línea `APP_KEY=` | añade `APP_KEY=` |
| **"The provided API key is invalid"** (Meili) | `MEILISEARCH_KEY` ≠ master key real | `grep master_key /etc/meilisearch.toml` → corrige + `optimize` |
| Productos **duplicados** (31→62→93) | `migrate --seed` en deploy actions cada push | quitar `--seed` (§10) + `migrate:fresh --seed` para limpiar |
| Otra app **pisa** tu índice/claves | servicios compartidos sin prefijo | `SCOUT_PREFIX` + `REDIS_PREFIX` (§12) |
| Toggle **Cola** no se activa (aun con paquete + scheduler) | quirk de UI del Toolkit | usar el **worker por scheduler** (§9 fallback) |
| BD: **`password authentication failed`** | typo en el usuario, o `#`/`$`/`&` en la password sin entrecomillar (el `#` corta el valor) | revisa el user; password alfanumérica (o `'comillas simples'`) + `config:clear` |
| `scout:import`: **"Model [...] not found"** (en el Toolkit) | la pestaña Artisan toma las comillas literales | **sin comillas**: `scout:import App\Models\Producto` |
| Imagen subida da **404** | falta el symlink `public/storage` | `php artisan storage:link` (§14) |
| Cambié `.env` y no surte efecto | config cacheada | Artisan → `optimize` |
| Página en blanco / código fuente / 403 | Document Root no apunta a `public/` | §4 |
| Assets/CSS 404 | `ASSET_URL`/`APP_URL` mal o `public/build` sin subir | fíjalos al dominio; versiona `public/build` |
| 500 *Permission denied* | artisan corrido como **root** | usar el Toolkit (corre como `cmurillo`) o `su - cmurillo` |

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

PostgreSQL:  127.0.0.1:5432 — cmurillo_testlaravel / cmurillo_laravel / E3&$8pOx7bngjhSe
Meilisearch: http://127.0.0.1:7700 — master key en /etc/meilisearch.toml
             (9f1211b0f1a0a2c7baf7f1b400e3bc89d8982cf9c3bcfdef181b71a3fcfa5156)
Redis:       127.0.0.1:6379 — phpredis disponible; caché en db 1
```

---

## 🔁 Resumen del ciclo de vida

| Acción | ¿Qué tocas? |
|---|---|
| Cambias código/vistas/rutas | solo `git push` (auto: migrate + scout:sync + optimize) |
| Cambias migraciones | `git push` (migrate corre solo) |
| Cambias el catálogo/seeder | `git push` + Toolkit → Artisan → `scout:import` una vez |
| Cambias dependencias (`composer.json`) | Toolkit → Composer → **Update/Install** |
| Cambias `.env` | Toolkit → Editar + Artisan → `optimize` |
| Tareas programadas / cola | toggles del Toolkit (cola: ver §9 + fallback) |
| Compilas estilos | `npm run build` en mini-server + `git push` |

---

## 🧰 Apéndice A — Método manual por SSH (fallback)

Cuando el Toolkit no esté disponible o un control falle (p.ej. el toggle Cola). **Ejecuta como `cmurillo`,
nunca como root.** Plantilla para correr cualquier artisan:

```bash
DOMAIN=lab138.littlebigpro.com
ART=$(find /var/www/vhosts/*/$DOMAIN -maxdepth 2 -name artisan -type f 2>/dev/null | head -1)
APP=$(dirname "$ART"); OWNER=$(stat -c '%U' "$ART")
su -s /bin/bash - "$OWNER" -c "cd '$APP' && /opt/plesk/php/8.4/bin/php artisan <COMANDO>"
```

Primer arranque, reset de duplicados, prefijos Meili y activación de Redis (bloques copia-pega listos):
ver **`docs/comandos-mantenimiento.md`**.

> Truco anti-pega: los bloques largos `su -c "…"` se desformatean al pegar desde un chat → usa siempre
> un **heredoc a `/tmp/script.sh`** (como en `comandos-mantenimiento.md`) o copia desde el botón de GitHub.
