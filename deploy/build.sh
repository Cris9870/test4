#!/usr/bin/env bash
#
# Compila los assets (Tailwind v4 + Vite) DENTRO de Plesk, durante el git-deploy.
# Lo llama el "script de despliegue" del Toolkit (paso 6). Ver DESPLIEGUE-PLESK.md §16.
#
# Resuelve el Node que instala Plesk en /opt/plesk/node/<NN>/bin.
# Vite 8 exige Node >=20.19 o >=22.12 -> ten instalado Node 22 (Plesk > Node.js).
#
set -euo pipefail
cd "$(dirname "$0")/.."   # raiz de la app (donde estan package.json y artisan)

NODE_BIN="$(ls -d /opt/plesk/node/*/bin 2>/dev/null | sort -V | tail -1 || true)"
if [ -z "$NODE_BIN" ]; then
  echo "ERROR: no hay Node en /opt/plesk/node/*/bin. Instala Node >=22 (Plesk > Node.js)." >&2
  exit 1
fi
export PATH="$NODE_BIN:$PATH"
echo "== Node $(node -v) | npm $(npm -v) | $NODE_BIN =="

# --include=dev: vite y tailwind son devDependencies; forzamos su instalacion
# aunque Plesk ponga NODE_ENV=production (que las omitiria y romperia el build).
npm ci --include=dev
npm run build

echo "== build OK -> public/build =="
ls -la public/build/manifest.json
