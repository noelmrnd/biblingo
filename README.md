# 🦉 Biblingo — App de Hábito de Lectura (Estilo Duolingo)

**Biblingo** (`biblingo.me`) es una plataforma gamificada diseñada para ayudar a las personas a construir y mantener un hábito diario de lectura. Inspira la consistencia mediante rachas de días consecutivos, ranking con amigos, recordatorios inteligentes y micro-interacciones.

---

## 📐 1. Arquitectura y Tecnologías

- **App Móvil / Web**: Vue 3 + Vite + Tailwind CSS + Capacitor (iOS & Android).
- **Backend / API**: PHP Nativo con arquitectura REST ligera + MariaDB / MySQL.
- **Web Promo**: HTML5 + CSS3 estático en la carpeta [`web/`](./web).
- **Autenticación**: *Sign in with Apple* (iOS) + *Google Sign-In* (Android / Web) + Panel Dev local.
- **Notificaciones**:
  - **Locales**: Recordatorio diario con ráfaga de 7 días escalonados (`@capacitor/local-notifications`).
  - **Push Remotas**: Notificación al añadir un amigo usando Firebase Cloud Messaging (FCM HTTP v1).
- **Social & Deep Links**: Hoja de compartir nativa (`@capacitor/share`) y recepción de invitaciones (`biblingo.me/invite/CODIGO`).

---

## 📁 2. Estructura del Proyecto

```
biblingo/
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
├── bin/                             # Scripts de utilidad
│   └── process_events.php           # Runner CLI de eventos de dominio
│
└── web/                             # Landing Page Estática Promo (biblingo.me)
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
MAIN_DB_NAME=biblingo
MAIN_DB_USERNAME=root
MAIN_DB_PASSWORD=root
MAIN_DB_PORT=3306
MAIN_DB_HOST=127.0.0.1
```

**Frontend (`app/.env`)**:
```env
VITE_API_BASE_URL=http://localhost:8084/api
VITE_GOOGLE_CLIENT_ID=TU_CLIENT_ID_OPCIONAL.apps.googleusercontent.com
```

---

## 🚀 5. Cómo Ejecutar en Desarrollo Local (Docker)

Todo el entorno de desarrollo se levanta mediante **Docker Compose**, leyendo los archivos directamente de tu sistema de archivos en vivo (sin necesidad de reconstruir la imagen):

```bash
docker compose up -d
# O usando los atajos de pnpm desde la raíz:
pnpm dev:d
```

Esto iniciará los servicios de desarrollo:
- 📱 **App Frontend (Vue 3 + Vite Dev Server con Hot Reload en vivo)**: [http://localhost:8083](http://localhost:8083) (o [http://localhost:5173](http://localhost:5173))
- 🌐 **Landing Page Web**: [http://localhost:8082](http://localhost:8082)
- 🐘 **API PHP Backend**: [http://localhost:8084/api](http://localhost:8084/api)
- 📬 **Worker de Eventos de Dominio**: Ejecutándose en background vía Supervisord (`process_events.php --daemon`)


### Comandos útiles:
- **Ver logs en tiempo real**: `docker compose logs -f` (o `pnpm dev:logs`)
- **Detener los servidores**: `docker compose down` (o `pnpm dev:down`)
- **Reconstruir la imagen de desarrollo**: `docker compose build` (o `pnpm dev:build`)

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

Desarrollado para **Biblingo** (`biblingo.me`). Todos los derechos reservados.
