#!/usr/bin/env bash
#
# Despliegue Laravel en Plesk (lab138) — pasos POST git-pull.
#
# IMPORTANTE: en este Plesk las "deployment actions" de Git NO se ejecutan solas
# (solo hace git pull). Este script es el paso fiable. Ejecutalo COMO EL USUARIO
# DE LA SUSCRIPCION (p.ej. cmurillo), NO como root, o romperas permisos:
#
#   su -s /bin/bash - cmurillo -c "bash /var/www/vhosts/<sub>/lab138.littlebigpro.com/deploy/deploy.sh"
#
# Uso:
#   bash deploy/deploy.sh           -> despliegue normal (migrate + settings + caches)
#   bash deploy/deploy.sh --seed    -> primer despliegue (ademas: siembra + reindexa Meili)
#
# 'composer install' NO se hace aqui: usa el modulo "PHP Composer" de Plesk (boton Instalar),
# que corre con el entorno correcto (los binarios composer/php no estan en el PATH del deploy).
set -euo pipefail

cd "$(dirname "$0")/.."   # raiz de la app (donde esta artisan)
echo "== Despliegue en $(pwd) =="

# 1) Binario PHP (Plesk no pone php en PATH; permite override con PHP_BIN=...)
PHP="${PHP_BIN:-}"
if [ -z "$PHP" ]; then
  for c in /opt/plesk/php/8.4/bin/php /opt/plesk/php/8.3/bin/php /opt/plesk/php/8.2/bin/php "$(command -v php || true)"; do
    if [ -n "$c" ] && [ -x "$c" ]; then PHP="$c"; break; fi
  done
fi
[ -n "$PHP" ] || { echo "ERROR: no encuentro el binario de PHP. Exporta PHP_BIN=/ruta/php"; exit 1; }
echo "PHP = $PHP"

# 2) vendor/ (si falta, avisa: instalalo con el modulo PHP Composer de Plesk)
if [ ! -f vendor/autoload.php ]; then
  echo "ERROR: falta vendor/. Instala dependencias con el modulo 'PHP Composer' de Plesk (boton Instalar) y reintenta."
  exit 1
fi

# 3) APP_KEY: generala si no hay una valida en .env
if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
  echo "Fijando APP_KEY nueva..."
  sed -i '/^APP_KEY=/d' .env
  echo "APP_KEY=base64:$(openssl rand -base64 32)" >> .env
fi

# 4) Migraciones + ajustes de indice (idempotentes)
"$PHP" artisan migrate --force
"$PHP" artisan scout:sync-index-settings

# 5) Solo primer despliegue: siembra + reindexa (separado para NO duplicar productos)
if [ "${1:-}" = "--seed" ]; then
  echo "Primer despliegue: sembrando y reindexando catalogo..."
  "$PHP" artisan db:seed --force
  "$PHP" artisan scout:flush 'App\Models\Producto'
  "$PHP" artisan scout:import 'App\Models\Producto'
fi

# 6) Cachear config/rutas/vistas (necesario tras tocar .env)
"$PHP" artisan optimize

echo "== DEPLOY OK =="
