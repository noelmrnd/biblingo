# Ideas descartadas o pausadas (contexto)

- **Racha compartida (buddy streak permanente)**: primera versión de la idea en `retos-lectura.md`. Consistía en elegir UN amigo como "compañero de lectura" fijo, con una racha combinada infinita (como la racha personal, pero condicionada a que ambos lean). Descartada por fricción social: cambiar de compañero o terminar la relación se siente como un rechazo. Se reemplazó por el concepto de retos con duración fija.
- **Auto-logout en 401/404 por status code genérico**: requiere que el backend distinga "cuenta eliminada" (ej. 410 Gone) de "recurso no encontrado" genérico (amigo no encontrado, invite code inválido) antes de poder implementarlo sin falsos positivos. Pendiente de trabajo de backend.
- **Persistir el tab activo entre relanzamientos de la app nativa**: descartado — el patrón esperado en apps de este tipo (Duolingo, Instagram) es abrir siempre en el tab principal (Racha), no en el último visitado.
