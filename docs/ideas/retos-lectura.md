# Retos de lectura entre amigos

**Estado**: diseñado, sin implementar.

**Problema que resuelve**: Home hoy es solo información personal (racha propia). La app se llama "lectura entre amigos" pero esa parte social no tiene presencia en la pantalla principal — el ranking vive escondido en la pestaña Amigos.

**Concepto**: invitas a un amigo a un reto de N días (3/7/14). Si ambos leen cada día del periodo, ganan. Si se rompe, el reto termina — fallido, sin drama, nadie tiene que "cortarlo". Al terminar (ganado o fallido) se acaba solo. Se puede iniciar otro cuando quieras, con el mismo o distinto amigo.

**Por qué retos de duración fija y no una "racha compartida" permanente** (idea descartada, ver `descartadas.md`): una pareja de lectura fija tiene fricción social — terminarla o cambiar de compañero puede sentirse como un rechazo. Los retos con fecha de fin evitan ese problema por diseño: la duración limitada ya está pactada desde el inicio, no hay exclusividad (puedes tener retos con varios amigos a la vez) y no hay acción de "romper" que alguien tenga que tomar.

## Reglas core

- Un reto activo por par de amigos a la vez (evita duplicados confusos), pero sin límite de retos simultáneos con amigos distintos.
- "Día compartido" = ambos usuarios tienen un registro en `reading_logs` con la misma fecha (en el calendario/tz de quien dispara el chequeo). Limitación conocida: parejas en timezones muy distintos pueden tener fricción de bordes de día — aceptable, documentar.
- Fallar (hueco de un día) = fin inmediato del reto, estado `failed`. Sin protectores de racha aplicables aquí — es apuesta corta y de alto riesgo por diseño, eso es lo que lo hace interesante.
- Completar los N días = estado `completed`, trofeo/badge.

## Modelo de datos propuesto

Tabla nueva `reading_challenges`:
```
id, user_a_id, user_b_id
status ENUM('pending','active','completed','failed')
duration_days INT        -- 3, 7 o 14
start_date DATE
current_day_streak INT   -- dias consecutivos donde ambos leyeron dentro del reto
last_completed_date
created_at
```

## Backend

- Endpoints: `POST /challenges/invite`, `POST /challenges/accept`, `GET /challenges` (activos del usuario).
- Hook en `ReadingController::logReading`: después de actualizar la racha personal, si el usuario tiene retos activos, checa si el otro participante ya leyó ese mismo día → avanza `current_day_streak`; si llega a `duration_days` → `completed`; si hay hueco → `failed`.
- Notificación día a día: nuevo `DomainEvent` (mismo patrón que `FriendNudgedEvent`) — "Reto con Ana: día 3/7, te falta hoy 🔥", vía FCM.

## Frontend

- Botón "Retar a leer N días" en `FriendProfileView`.
- Card "Retos activos" en Home (Dashboard): lista corta, cada uno con nombre del amigo + progreso "día X/N" + barra. No es un slot fijo permanente, son N cards según cuántos retos activos haya.

## Preguntas abiertas / por definir

- ¿Qué duraciones ofrecer exactamente? (propuesta inicial: 3, 7, 14 días)
- ¿Qué gana el que completa un reto? (¿solo badge/trofeo, o algo más — ej. protector de racha bonus?)
- ¿Cómo se resuelve el borde de timezones muy distintos entre los dos participantes?
- ¿Límite de retos activos simultáneos por usuario (con distintos amigos) o sin límite?
