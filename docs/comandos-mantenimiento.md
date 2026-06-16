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

> ℹ️ Estos son comandos de **configuración puntual**. El despliegue de código del día a día es
> **solo `git push`** (las deploy actions corren migrate + scout:sync + optimize solas).
