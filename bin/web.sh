#!/usr/bin/env bash

# Biblingo - Servidor local de desarrollo para la carpeta web (Landing Page)

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "🌐 Iniciando Landing Page Web en http://0.0.0.0:8080 ..."
php -S 0.0.0.0:8080 -t "$ROOT_DIR/web"
