// Cache de meses del calendario de lectura, compartido entre montajes de
// MonthlyTracker.vue. Un `<script setup>` no da scope de modulo real (todo su
// contenido corre dentro de setup(), se re-crea en cada instancia) — por eso
// este Map vive en un .js aparte, que si se evalua una sola vez al importarse.
// Sobrevive a que el componente se desmonte al cambiar de tab; se pierde solo
// si se cierra la app. Clave 'YYYY-M', valor: array de fechas leidas.
export const monthCache = new Map();
