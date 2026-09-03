# 🦉 Libringo — App de Hábito de Lectura (Estilo Duolingo)

**Libringo** (`libringo.com`) es una plataforma gamificada diseñada para ayudar a las personas a construir y mantener un hábito diario de lectura. Inspira la consistencia mediante rachas de días consecutivos, ranking con amigos, recordatorios inteligentes y micro-interacciones.

---

## 📐 1. Arquitectura y Tecnologías

- **App Móvil / Web**: Vue 3 + Vite + Tailwind CSS + Capacitor (iOS & Android).
- **Backend / API**: PHP Nativo con arquitectura REST ligera + MariaDB / MySQL.
- **Web Promo**: HTML5 + CSS3 estático en la carpeta [`web/`](./web).
- **Autenticación**: *Sign in with Apple* (iOS) + *Google Sign-In* (Android / Web) + Panel Dev local.
- **Notificaciones**:
  - **Locales**: Recordatorio diario con ráfaga de 7 días escalonados (`@capacitor/local-notifications`).
  - **Push Remotas**: Notificación al añadir un amigo usando Firebase Cloud Messaging (FCM HTTP v1).
- **Social & Deep Links**: Hoja de compartir nativa (`@capacitor/share`) y recepción de invitaciones (`libringo.com/invite/CODIGO`).

---

## 📁 2. Estructura del Proyecto

```
libringo/
├── api/                             # Backend API en PHP Nativo
│   ├── config/
│   │   └── db.php                   # Conexión PDO y utilidades de respuesta JSON
│   ├── public/
│   │   └── index.php                # Enrutador REST (/api/auth/social, /api/reading/*, /api/friends/*)
│   ├── src/
│   │   ├── Controllers/             # Controladores (Auth, Lecturas, Amigos, Usuario)
│   │   └── Services/                # FCM Push Notification Service
│   ├── .env                         # Variables de entorno de la base de datos
│   └── schema.sql                   # Esquema SQL MariaDB (users, reading_logs, friendships)
│
├── app/                             # Frontend App (Vue 3 + Vite + Tailwind + Capacitor)
│   ├── capacitor.config.json        # Configuración nativa de Capacitor
│   ├── src/
│   │   ├── services/                # API, Autenticación, Notificaciones, Share & Deep Links
│   │   ├── views/                   # Vistas (LoginView, DashboardView, FriendsView, ProfileView)
│   │   ├── App.vue                  # Componente principal y navegación
│   │   └── style.css                # Estilos gamificados 3D estilo Duolingo
│   ├── .env                         # Variables de entorno (VITE_API_BASE_URL)
│   └── package.json
│
├── bin/                             # Scripts de Desarrollo Local Executables
│   ├── dev.sh                       # Levanta API PHP (8000) y App Vue (3000) simultáneamente
│   ├── api.sh                       # Levanta únicamente la API PHP
│   └── app.sh                       # Levanta únicamente la App Vue (pnpm dev)
│
└── web/                             # Landing Page Estática Promo (libringo.com)
    ├── index.html                   # Página de aterrizaje con redirección inteligente
    └── style.css                    # Estilos CSS
```

---

## ⚡ 3. Requisitos Previos

- **PHP** >= 8.0
- **MariaDB** / **MySQL**
- **Node.js** >= 18
- **pnpm** (Gestor de paquetes recomendado)

---

## 🛠️ 4. Configuración Inicial

### Paso 1: Clonar e instalar dependencias de la app
```bash
cd app
pnpm install
cd ..
```

### Paso 2: Crear e importar la Base de Datos
Importa el esquema en tu servidor MySQL/MariaDB local:
```bash
mysql -u root -proot -h 127.0.0.1 < api/schema.sql
```

### Paso 3: Configurar Variables de Entorno

**Backend (`api/.env`)**:
```env
MAIN_DB_NAME=libringo
MAIN_DB_USERNAME=root
MAIN_DB_PASSWORD=root
MAIN_DB_PORT=3306
MAIN_DB_HOST=127.0.0.1
```

**Frontend (`app/.env`)**:
```env
VITE_API_BASE_URL=http://localhost:8000/api
VITE_GOOGLE_CLIENT_ID=TU_CLIENT_ID_OPCIONAL.apps.googleusercontent.com
```

---

## 🚀 5. Cómo Ejecutar en Desarrollo Local

Puedes usar los scripts optimizados de la carpeta `bin/`:

### Opción A: Levantar todo en un solo comando (Recomendado)
```bash
./bin/dev.sh
# O usando pnpm desde la raíz:
pnpm dev
```
Esto iniciará:
- 🐘 **API PHP** en `http://localhost:8000`
- ⚡ **App Vue (Vite)** en `http://localhost:3000` (o `http://localhost:5173`)

### Opción B: Levantar por separado
- **Solo API PHP**: `./bin/api.sh` o `pnpm dev:api`
- **Solo App Vue**: `./bin/app.sh` o `pnpm dev:app`

---

## 🧪 6. Modo de Prueba Local (Panel Dev)

En entorno de desarrollo (`localhost`), la pantalla de inicio incluye el panel **`🛠️ Entorno de Desarrollo`**:
1. Escribe cualquier nombre (ej. `Lector 1`, `Lector 2`).
2. Haz clic en **"Entrar Dev"** para iniciar sesión instantáneamente sin necesidad de credenciales de Google o Apple.
3. Abre dos pestañas en el navegador con nombres distintos para probar la interacción de amigos, el ranking de rachas y las invitaciones.

---

## 📖 7. Funcionalidades de la App

1. **Dashboard de Racha (`DashboardView.vue`)**:
   - Llama brillante animada con contador de días consecutivos.
   - Botón 3D *"Marcar Lectura de Hoy"* con efecto de confeti y actualización idempotente.
   - Cronómetro de lectura de 10 minutos con aviso de meta cumplida.
   - Tracker semanal de 7 días (Lun - Dom).
2. **Sección de Amigos (`FriendsView.vue`)**:
   - Generador de **Código QR** dinámico (`qrcode`) para escaneo directo entre teléfonos.
   - Generador y copia de código único de invitación (`invite_code`).
   - Botón *"Compartir Enlace"* nativo (`@capacitor/share`).
   - Formulario para agregar amigos por código o imagen QR.
   - Tabla de clasificación (ranking) ordenada por número de racha.
3. **Perfil y Notificaciones (`ProfileView.vue`)**:
   - Selector de hora para recordatorio diario de lectura.
   - Programación automática de ráfaga de 7 días escalonados (`@capacitor/local-notifications`).

---

## 📄 8. Licencia y Créditos

Desarrollado para **Libringo** (`libringo.com`). Todos los derechos reservados.
