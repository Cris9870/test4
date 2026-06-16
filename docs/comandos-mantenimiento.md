# 🛠️ Comandos de mantenimiento — lab138 (copia-pega limpio)

Comandos puntuales para el **shell root** del servidor. Ábrelo en GitHub y usa el **botón de copiar**
(esquina del bloque de código) para que no se desformatee al pegar.

> Todos ejecutan `artisan` **como el usuario de la suscripción** (`cmurillo`), nunca como root,
> para no romper permisos. No son parte del despliegue normal (eso es solo `git push`).

---

## 1. Prefijo de índice Meili + aplicar `APP_DEBUG` + sondear Redis

Pone `SCOUT_PREFIX=lab138_` (el índice pasa a `lab138_productos`), borra el índice viejo `productos`,
reindexa, re-cachea (aplica el `APP_DEBUG=false` que pusiste en el `.env`) y muestra los índices de
Meili y si hay `phpredis`.

```bash
ART=$(find /var/www/vhosts/*/lab138.littlebigpro.com -maxdepth 2 -name artisan -type f 2>/dev/null | head -1)
APP=$(dirname "$ART"); OWNER=$(stat -c '%U' "$ART"); echo ">> APP=$APP OWNER=$OWNER"
cat > /tmp/fix.sh <<'SCRIPT'
set -e
APP="$1"; PHP=/opt/plesk/php/8.4/bin/php
cd "$APP"
if grep -q 'function searchableAs' app/Models/Producto.php; then echo "CODIGO_VIEJO: el auto-deploy aun no actualizo el modelo; reintenta en ~30s"; exit 1; fi
if grep -q '^SCOUT_PREFIX=' .env; then sed -i 's/^SCOUT_PREFIX=.*/SCOUT_PREFIX=lab138_/' .env; else printf '\nSCOUT_PREFIX=lab138_\n' >> .env; fi
MK=$(grep '^MEILISEARCH_KEY=' .env | cut -d= -f2-)
curl -s -X DELETE -H "Authorization: Bearer $MK" http://127.0.0.1:7700/indexes/productos >/dev/null || true
$PHP artisan config:clear
$PHP artisan scout:sync-index-settings
$PHP artisan scout:import 'App\Models\Producto'
$PHP artisan optimize
echo "--- APP_DEBUG ---"; grep '^APP_DEBUG=' .env || true
echo "--- indices Meili ---"; curl -s -H "Authorization: Bearer $MK" http://127.0.0.1:7700/indexes; echo
echo "--- phpredis? ---"; $PHP -m | grep -i redis || echo "phpredis: NO"
echo "--- redis ping ---"; redis-cli -h 127.0.0.1 -p 6379 ping 2>/dev/null || echo "redis-cli no disp"
echo "PREFIX_OK"
SCRIPT
chmod a+rx /tmp/fix.sh
su -s /bin/bash - "$OWNER" -c "bash /tmp/fix.sh '$APP'"
```

---

## 2. Plantilla: correr cualquier `artisan`

Sustituye `<COMANDO>` por lo que necesites (`migrate --force`, `optimize`, `about`, etc.).

```bash
ART=$(find /var/www/vhosts/*/lab138.littlebigpro.com -maxdepth 2 -name artisan -type f 2>/dev/null | head -1)
APP=$(dirname "$ART"); OWNER=$(stat -c '%U' "$ART")
su -s /bin/bash - "$OWNER" -c "cd '$APP' && /opt/plesk/php/8.4/bin/php artisan <COMANDO>"
```

---

## 3. Limpiar duplicados del catálogo (reset a 31 productos)

```bash
ART=$(find /var/www/vhosts/*/lab138.littlebigpro.com -maxdepth 2 -name artisan -type f 2>/dev/null | head -1)
APP=$(dirname "$ART"); OWNER=$(stat -c '%U' "$ART")
cat > /tmp/reset.sh <<'SCRIPT'
set -e
APP="$1"; PHP=/opt/plesk/php/8.4/bin/php
cd "$APP"
$PHP artisan migrate:fresh --seed --force
$PHP artisan scout:flush 'App\Models\Producto'
$PHP artisan scout:import 'App\Models\Producto'
$PHP artisan optimize
echo "RESET_OK"
SCRIPT
chmod a+rx /tmp/reset.sh
su -s /bin/bash - "$OWNER" -c "bash /tmp/reset.sh '$APP'"
```

---

## 4. Aplicar un cambio de `.env` (re-cachear)

Tras editar el `.env` por File Manager, hay que re-cachear para que surta efecto:

```bash
ART=$(find /var/www/vhosts/*/lab138.littlebigpro.com -maxdepth 2 -name artisan -type f 2>/dev/null | head -1)
APP=$(dirname "$ART"); OWNER=$(stat -c '%U' "$ART")
su -s /bin/bash - "$OWNER" -c "cd '$APP' && /opt/plesk/php/8.4/bin/php artisan optimize"
```

---

## 5. Activar Redis (caché) con prefijo + asegurar `APP_DEBUG=false`

`phpredis` está disponible en el PHP de Plesk y el `redis-server` responde, así que **no hace falta
Composer**. Esto pone la caché en Redis con prefijo `lab138_` (así no choca con otras apps en el
mismo Redis), fija `APP_DEBUG=false` explícito, y comprueba que Redis funciona escribiendo y leyendo
una clave (la verás namespaceada con `lab138_`).

```bash
ART=$(find /var/www/vhosts/*/lab138.littlebigpro.com -maxdepth 2 -name artisan -type f 2>/dev/null | head -1)
APP=$(dirname "$ART"); OWNER=$(stat -c '%U' "$ART"); echo ">> APP=$APP OWNER=$OWNER"
cat > /tmp/redis.sh <<'SCRIPT'
set -e
APP="$1"; PHP=/opt/plesk/php/8.4/bin/php
cd "$APP"
setenv() { if grep -q "^$1=" .env; then sed -i "s|^$1=.*|$1=$2|" .env; else printf '%s=%s\n' "$1" "$2" >> .env; fi; }
setenv APP_DEBUG false
setenv REDIS_CLIENT phpredis
setenv REDIS_HOST 127.0.0.1
setenv REDIS_PORT 6379
setenv REDIS_PREFIX lab138_
setenv CACHE_STORE redis
$PHP artisan optimize
echo "--- prueba escribir/leer en Redis ---"
$PHP artisan tinker --execute="Cache::put('redis_test_key','funciona',120); echo 'Cache::get => '.Cache::get('redis_test_key').PHP_EOL;"
echo "--- claves en Redis (cache = db 1) con prefijo lab138_ ---"
redis-cli -n 1 -h 127.0.0.1 -p 6379 --scan --pattern 'lab138_*' | head -20
echo "REDIS_OK"
SCRIPT
chmod a+rx /tmp/redis.sh
su -s /bin/bash - "$OWNER" -c "bash /tmp/redis.sh '$APP'"
```

Esperado: `Cache::get => funciona` y al menos una clave `lab138_..._cache_redis_test_key` en el listado.
(Nota: Laravel **ya namespacea Redis por `APP_NAME`** por defecto; el `REDIS_PREFIX` lo hace explícito.
Además la **caché usa la DB 1** de Redis (`REDIS_CACHE_DB=1`), por eso el `--scan` lleva `-n 1`:
doble aislamiento = prefijo + base de datos separada.)

---

> ℹ️ Estos son comandos de **configuración puntual**. El despliegue de código del día a día es
> **solo `git push`** (las deploy actions corren migrate + scout:sync + optimize solas).
