#!/usr/bin/env bash

# Libringo - Servidor Frontend Vue (pnpm)

BLUE='\033[0;34m'
NC='\033[0m'

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo -e "${BLUE}⚡ Iniciando Frontend App (pnpm dev)...${NC}"
pnpm --prefix "$ROOT_DIR/app" dev
