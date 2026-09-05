# Rescate social de racha (racha congelada + regalo de protector)

**Estado**: diseñado, sin implementar. Depende de los protectores de racha (ya implementado: `streak_freezes`, `streak_freezes_used` en `users`, lógica en `ReadingController::logReading`).

**Problema que resuelve**: hoy el protector de racha (capa 1) es 100% individual y silencioso — cubre un hueco de 1 día sin que nadie se entere. Es útil pero no aprovecha el ángulo social de la app. Esta idea agrega una segunda capa que sí depende de amigos.

**Origen**: nace de comparar con Duolingo. Duolingo NO permite regalar protectores a amigos — los suyos (Streak Freeze) son personales, se compran con gemas o vienen con Super. Sí tienen "Streak Repair" (pagar gemas para restaurar una racha perdida en una ventana corta), pero también personal. La idea aquí es socializar ese segundo concepto: en vez de pagar tú, un amigo "paga" con su propio protector para rescatarte.

## Mecánica

Dos capas de protección, en orden:

1. **Protector automático propio** (ya implementado): cubre en silencio un hueco de exactamente 1 día. La racha nunca se rompe, nadie se entera.
2. **Racha congelada + rescate social** (esta idea, nueva): se activa cuando la capa 1 no alcanzó (sin protectores propios disponibles, o hueco de 2+ días). En ese caso:
   - La racha SÍ se rompe, pero en vez de perderse el número, se mueve a un contador `frozen_streak_count` (racha congelada) y `streak_count` vuelve a 0.
   - Ejemplo: tenía racha 200, se le rompe → `streak_count = 0`, `frozen_streak_count = 200`.
   - Si el usuario vuelve a leer, su racha arranca en 1 normalmente (`streak_count = 1`), pero mientras `frozen_streak_count > 0` y no haya expirado la ventana de rescate, un amigo puede "rescatarlo": el amigo gasta 1 de sus propios protectores, y se hace el merge: `streak_count_nuevo = streak_count_actual + frozen_streak_count` (1 + 200 = 201), `frozen_streak_count = 0`. Si supera el máximo histórico, se actualiza `max_streak_count`.
   - **Ventana de rescate**: propuesta inicial 48h desde que se rompió la racha. Si expira sin rescate, `frozen_streak_count` se pierde permanentemente (ahí está el drama/urgencia — sin ventana, sería un salvavidas infinito sin riesgo real).
   - **Requiere que el usuario ya haya leído hoy** antes de poder ser rescatado (si no, no hay nada que fusionar con la racha congelada — no tendría sentido narrativo "regalar" sobre un día vacío).
   - **Costo real para quien rescata**: gasta 1 protector propio. Sin este costo sería un botón gratis sin peso emocional.

## Modelo de datos propuesto

En `users`: agregar `frozen_streak_count INT DEFAULT 0` y `frozen_streak_expires_at TIMESTAMP NULL`.

Al romperse una racha (detectado en `ReadingController::logReading` cuando el hueco no se cubre con protector propio):
```
frozen_streak_count = streak_count (el que tenía antes de romperse)
frozen_streak_expires_at = now() + 48h
streak_count = 0 (o 1 si esa misma lectura ya cuenta como hoy)
```

Nuevo endpoint `POST /friends/rescue` (`user_id` del rescatador, `friend_id` del rescatado):
- Valida amistad (mismo patrón que `getFriendHistory`).
- Valida: rescatador tiene `streak_freezes > 0`, rescatado tiene `frozen_streak_count > 0` y `frozen_streak_expires_at > now()`, rescatado ya leyó hoy.
- Aplica merge, descuenta protector del rescatador, incrementa su `streak_freezes_used`.
- Dispara `DomainEvent` (mismo patrón que `FriendNudgedEvent`) → push al rescatado: "¡Ana te regaló un protector y salvó tu racha de 200 días! 🎁🧊".

## Frontend

- `FriendProfileView`: si el amigo tiene `frozen_streak_count > 0` y ventana vigente, mostrar botón "Rescatar racha 🎁🧊" (solo si el usuario actual tiene protectores disponibles).
- Indicador de cuenta regresiva de la ventana de rescate (ej. "Puedes rescatarlo por 21h más").

## Preguntas abiertas / por definir

- ¿48h es la ventana correcta, o debería ser más corta (24h) para más urgencia?
- ¿Puede rescatar cualquier amigo, o solo alguien con quien se tenga un reto activo (ver `retos-lectura.md`)? Propuesta inicial: cualquier amigo, independiente de retos.
- ¿Qué pasa si dos amigos intentan rescatar al mismo tiempo? Resolver con el primero que complete la transacción (lock optimista o transacción atómica), el segundo ve "ya fue rescatado".
- ¿Se notifica al usuario cuando su racha se congela (para darle chance de pedir rescate activamente), o solo se entera si un amigo lo rescata sin avisar?
