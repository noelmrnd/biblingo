#!/usr/bin/env bash

# Biblingo - Script de desarrollo local unificado

# Colores para la consola
GREEN='\030[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🚀 Iniciando entorno de desarrollo local de Biblingo...${NC}\n"

# Obtener directorio raíz del proyecto
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Función de limpieza al presionar Ctrl+C
cleanup() {
    echo -e "\n${YELLOW}🛑 Deteniendo servidores de desarrollo...${NC}"
    kill 0
    exit 0
}

trap cleanup SIGINT SIGTERM EXIT

# 1. Iniciar Servidor Backend PHP API
echo -e "${GREEN}🐘 Iniciando API PHP en http://0.0.0.0:8000 ...${NC}"
php -S 0.0.0.0:8000 -t "$ROOT_DIR/api/public" &
PHP_PID=$!

# 2. Iniciar Servidor Frontend (Vue + Vite con pnpm)
echo -e "${BLUE}⚡ Iniciando Frontend App (pnpm dev)...${NC}"
pnpm --prefix "$ROOT_DIR/app" dev &
VITE_PID=$!

# 3. Iniciar Servidor Landing Page Web
echo -e "${YELLOW}🌐 Iniciando Landing Page Web en http://0.0.0.0:8080 ...${NC}"
php -S 0.0.0.0:8080 -t "$ROOT_DIR/web" &
WEB_PID=$!

# Esperar a que los procesos terminen
wait $PHP_PID $VITE_PID $WEB_PID
