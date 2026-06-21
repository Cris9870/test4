#!/usr/bin/env bash
#
# Verifica el despliegue en vivo (smoke test por HTTP). No necesita estar en el server:
# se puede correr desde cualquier sitio con acceso a la URL publica.
#
# Uso:  bash deploy/verify.sh [URL]
#       bash deploy/verify.sh https://lab138.littlebigpro.com
#
URL="${1:-https://lab138.littlebigpro.com}"
fail=0

check() { # $1 descripcion  $2 path  $3 needle
  local out code body
  out=$(curl -sS -m 20 -w $'\n%{http_code}' "$URL$2" 2>/dev/null || true)
  code=$(printf '%s' "$out" | tail -n1)
  body=$(printf '%s' "$out" | sed '$d')
  if [ "$code" = "200" ] && printf '%s' "$body" | grep -q -- "$3"; then
    printf 'OK    %s  (%s)\n' "$1" "$2"
  else
    printf 'FALLO %s  (%s) -> HTTP %s\n' "$1" "$2" "$code"
    fail=1
  fi
}

echo "== Verificando $URL =="
check "Home + PostgreSQL"            "/"                       "PostgreSQL"
check "Home + Meilisearch"          "/"                       "Meilisearch"
check "Home con anuncios"           "/"                       "/anuncio/"
check "Busqueda 'bici'"             "/buscar?q=bici"          "/anuncio/"
check "Busqueda 'laptop'"           "/buscar?q=laptop"        "/anuncio/"
check "Faceta categoria=Tecnologia" "/buscar?categoria=Tecnolog%C3%ADa" "/anuncio/"
check "Detalle SSR de anuncio"      "/anuncio/1"              "S/"

if [ "$fail" -eq 0 ]; then echo "== TODO OK =="; else echo "== HAY FALLOS =="; exit 1; fi
