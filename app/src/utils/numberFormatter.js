/**
 * Formatea un numero con separador de miles ',' (ej. 1234 -> "1,234").
 *
 * @param {number|string|null|undefined} value
 * @returns {string} Numero formateado, o '0' si value es invalido/nulo.
 */
export function formatNumber(value) {
  const n = Number(value);
  if (!Number.isFinite(n)) return '0';
  return n.toLocaleString('en-US');
}
