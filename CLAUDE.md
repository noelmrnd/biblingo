# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

**Biblingo** (`biblingo.me`) — gamified reading-with-friends app. Daily reading streaks, friend rankings, reminders. Vue 3 mobile/web app + PHP REST API + static promo landing page.

- **App**: Vue 3 + Vite + Tailwind CSS + Capacitor (iOS & Android) — `app/`
- **API**: Native PHP (no framework) + MariaDB/MySQL — `api/`
- **Landing**: static HTML/CSS — `web/`
- **Auth**: Sign in with Apple (iOS), Google Sign-In (Android/Web), plus a local dev-only panel (no real credentials needed)
- **Push**: Firebase Cloud Messaging (FCM HTTP v1) for friend-related notifications; local notifications for daily reminders (`@capacitor/local-notifications`)
- **Deep links**: `biblingo.me/invite/CODIGO` for friend invites, handled via `@capacitor/share` + `app/src/services/deepLinks.js`

## Running locally

Preferred: Docker Compose (bind-mounts source, no rebuild needed on edits):

```bash
pnpm dev        # docker compose up
pnpm dev:d      # detached
pnpm dev:logs
pnpm dev:down
pnpm dev:build  # rebuild image after Dockerfile/dependency changes
```

Services: web landing on `:8082`, API on `:8084/api`, app (Vite dev server, HMR) on `:8083` / `:5173`. The domain-events worker runs inside the `biblingo` container under supervisord (`process_events.php --daemon`).

Non-Docker alternative (`bin/*.sh`): `bin/dev.sh` runs API (`php -S 0.0.0.0:8000`), Vite app, and web landing (`0.0.0.0:8080`) together; `bin/api.sh`, `bin/app.sh`, `bin/web.sh` run them individually. Note the ports differ from the Docker setup (8000/8080 vs 8084/8082).

Initial setup: `pnpm install` in `app/`, import `api/schema.sql` into MySQL/MariaDB, configure `api/.env` and `app/.env` (see README for the required keys — `MAIN_DB_*`, `VITE_API_BASE_URL`).

In dev (`localhost`), the login screen exposes a **"🛠️ Entorno de Desarrollo"** panel: type any display name and click "Entrar Dev" to sign in instantly without Google/Apple credentials — the standard way to test locally, including multi-tab friend/streak interactions.

## App commands (`app/`)

```bash
pnpm dev       # vite dev server
pnpm build     # vite build
pnpm preview
pnpm assets    # regenerate capacitor icons/splash screens
```

No test suite or lint script is configured for either `app/` or `api/`.

## Architecture

### API (`api/`)

Single front-controller router, no framework: `api/public/index.php` reads `$_SERVER['REQUEST_URI']`/method and dispatches to static `Controller::method()` calls in `api/src/Controllers/` (`AuthController`, `ReadingController`, `FriendController`, `UserController`). `user_id` is passed via query string or JSON body — there is no session/token auth layer beyond the social-login handshake. `api/config/db.php` provides the PDO connection + `sendJsonResponse()`/`getJsonInput()` helpers used everywhere.

IDs are 64-bit Snowflake IDs (`api/src/Utils/SnowflakeGenerator.php`), not auto-increment — used as primary keys across `users`, `reading_logs`, `friendships`, etc. (`api/schema.sql`).

**Domain events (outbox pattern)**: side effects that need to happen after a request (e.g. pushing an FCM notification when a friend request is accepted) are not fired inline. Controllers write a row to `domain_events` via `DomainEventStore::record()` (`api/src/Events/*` define the event payloads: `FriendAddedEvent`, `FriendNudgedEvent`, `FriendRequestAcceptedEvent`, `FriendRequestSentEvent`). A separate worker, `bin/process_events.php`, polls `domain_events` for `status = 'pending'` and dispatches them through `DomainEventProcessor::processPending()`, which routes by `event_name` to a handler that calls `FCMService`. This worker runs continuously via supervisord in Docker (`docker/supervisord.conf`) — when adding a new async side effect to a controller action, add a new `DomainEvent` subclass and a case in `DomainEventProcessor::handleEvent()`, don't call `FCMService` directly from the controller.

Friendships are bidirectional and stored as two logically-linked concepts: `friend_requests` (pending, sender/receiver) and `friendships` (accepted, symmetric pair rows) — see `FriendController` for the accept/reject/cancel/remove flow.

### App (`app/src/`)

Flat structure, no router library — `App.vue` owns top-level navigation/view switching. Views live in `app/src/views/` (`LoginView`, `DashboardView`, `FriendsView`, `ProfileView`); reusable UI in `app/src/components/`. Cross-cutting concerns are isolated into single-purpose services under `app/src/services/`: `api.js` (HTTP calls to the PHP backend), `authService.js` (social login + dev panel), `notifications.js` (local notification scheduling for the 7-day reminder burst), `deepLinks.js` (invite links), `shareService.js`, `storage.js` (Capacitor Preferences wrapper), `toast.js`, `review.js` (in-app review prompts), `userService.js`.

Capacitor config is split by environment: `capacitor.config.dev.json` / `.prod.json` are copied over `capacitor.config.json` depending on target (check which one is active before native builds — `capacitor.config.json` is the file Capacitor CLI actually reads).

### Web (`web/`)

Static, no build step — plain HTML/CSS files served directly (landing page, `privacidad.html`, `terminos.html`, `contacto.html`, `eliminar-cuenta.html`).

## Releases

CI (`.github/workflows/ci.yml`) triggers only on `v*.*.*` tags: builds the Vue app, then builds and pushes a single all-in-one Docker image (`docker/Dockerfile`) to `ghcr.io/<repo>` tagged by semver. There is no CI on pushes/PRs to branches.
