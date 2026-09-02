#!/usr/bin/env bash

# Biblingo - Servidor de API PHP

GREEN='\033[0;32m'
NC='\033[0m'

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo -e "${GREEN}🐘 Iniciando servidor API PHP en http://0.0.0.0:8000 ...${NC}"
php -S 0.0.0.0:8000 -t "$ROOT_DIR/api/public"
