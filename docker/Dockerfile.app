FROM node:22-slim

RUN corepack enable

WORKDIR /app

# Copiar manifiestos y configuración de pnpm para aprovechar el caché de Docker
COPY app/package.json app/pnpm-lock.yaml app/pnpm-workspace.yaml ./
RUN pnpm install --frozen-lockfile

EXPOSE 3000

CMD ["pnpm", "dev", "--host"]
