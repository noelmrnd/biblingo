/**
 * Elige singular o plural segun count. Por defecto el plural es singular + 's'
 * (sirve para la mayoria de sustantivos en espanol); pasar uno explicito para
 * los que no siguen esa regla (ej. pluralize(n, 'mes', 'meses')).
 *
 * @param {number} count
 * @param {string} singular
 * @param {string} [plural]
 * @returns {string}
 */
export function pluralize(count, singular, plural = `${singular}s`) {
  return Math.abs(count) === 1 ? singular : plural;
}
