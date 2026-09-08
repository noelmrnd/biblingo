# SHA fingerprints — Android (Libringo, `com.libringo.app`)

Registrados en Firebase Console (proyecto `libringo`) → Project Settings → App Android → Add fingerprint.

## Release / Upload key
Archivo: `app/android/keystore/biblingo-release.keystore` (alias `biblingo`)
- SHA-1: `FF:66:69:7C:54:81:45:32:5E:63:15:8C:B3:56:31:96:C2:A8:66:54`
- SHA-256: `5D:95:55:22:BC:F0:AB:51:C6:9B:41:07:14:E4:BC:5A:07:04:E8:CE:6C:C7:BF:3B:1D:2A:3D:AD:9F:B2:8A:E0`

## Play App Signing (cert real que firma lo que baja el usuario desde la Play Store)
Play Console → Integridad de la app.

- **Clave de firma de la app (actual/activa)** — la que van a tener TODOS los builds
  que se suban de aca en adelante, en cualquier track:
  - SHA-1: `D3:EF:9D:D5:D6:03:A5:B5:8B:D5:42:31:18:DD:30:48:50:64:77:E9`
- **Claves de firma de aplicaciones anteriores** (legacy, ya no se usan para builds nuevos):
  - SHA-1: `3C:D0:F0:94:03:23:DE:28:60:AA:F5:4F:FA:38:E4:3B:B2:AC:9A:90`
- SHA-256 de ambas: pendiente — sacar de Play Console → App integrity.

### Caso raro (2026-09-08): tercera SHA-1 no listada en Play Console
Un build bajado del link de opt-in a "Pruebas internas" (`play.google.com/apps/internaltest/.../join`,
instalado via `com.android.vending`, no sideload/internal app sharing) resulto firmado con:
`3E:4E:C3:43:A3:77:A0:84:64:AF:8F:63:A2:DE:8A:9C:02:96:E9:3B`

Confirmado con `apksigner verify --print-certs base.apk` (extraido del device con
`adb pull` sobre el path de `adb shell pm path com.libringo.app`), DN generico de
Play Signing (`CN=Android, OU=Android, O=Google Inc.`) — no coincide con la clave
actual ni con la anterior listadas arriba, y Play Console no mostraba mas entradas
bajo "anteriores". Causa exacta no identificada (probable cache/track especifico de
Play para ese build puntual). Se agrego tambien a Firebase junto con las otras dos
para no bloquear ese build ya instalado; los builds futuros deberian usar solo la
clave actual (`D3EF9D...`).

**Sintoma si falta alguna de estas 3 en Firebase**: login con Google se queda colgado
tras elegir cuenta, sin error visible en la UI (el catch de `authService.js` nunca
se dispara — el fallo es nativo, antes de llegar a JS). En logcat aparece:
`ClassNotFoundException: com.google.android.gms.auth.api.identity.GetSignInIntentRequest`
y `BadParcelableException` justo despues del `GoogleProvider: Google login: ... signingSha1=...`.

Google Sign-In solo valida SHA-1, no hace falta agregar SHA-256 para esto.

## Debug — viejo (proyecto biblingo, `~/.android/debug.keystore` de alguna máquina)
- SHA-1: `34:2F:EC:60:A1:45:DB:90:75:41:46:79:5A:4A:AA:BF:DA:3F:00:E1`
- Estado: no migrado al proyecto libringo (se reemplaza por el debug.keystore compartido de abajo)

## Debug — compartido (nuevo, commiteado en el repo)
Archivo: `app/android/debug.keystore` (alias `androiddebugkey`, pass `android`/`android`)
- SHA-1: `9B:DF:25:BC:9B:65:90:5E:6A:15:EB:73:7A:32:5F:3C:A0:FD:2A:AE`
- SHA-256: `3C:0D:2A:D7:CA:D9:54:D1:70:94:FD:68:DE:8A:B8:C1:05:18:E5:F1:AE:59:48:32:2A:30:DC:A4:A5:0C:2F:74`
- Válido 30 años desde 2026-09-07
- Usado por `buildTypes.debug.signingConfig` en `app/android/app/build.gradle`

## assetlinks.json (`app/public/.well-known/assetlinks.json`)
Debe listar los fingerprints SHA-256 que realmente puedan servir Universal/App Links en producción: release/upload + Play App Signing. El debug no necesita estar ahí (solo apps instaladas desde Play usan App Links).
