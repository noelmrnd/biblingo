#!/usr/bin/env bash

# Libringo - Servidor local de desarrollo para la carpeta web (Landing Page)

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "🌐 Iniciando Landing Page Web (Astro dev server) en http://0.0.0.0:8080 ..."
pnpm --prefix "$ROOT_DIR/web" exec astro dev --host 0.0.0.0 --port 8080
