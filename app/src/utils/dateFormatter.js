/**
 * Convierte una fecha a string YYYY-MM-DD usando la zona horaria local.
 * 
 * @param {string|Date|number|null} dateInput 
 * @returns {string} Fecha en formato 'YYYY-MM-DD' o '' si es inválida.
 */
export function toLocalDateString(dateInput = new Date()) {
  if (!dateInput) return '';
  if (typeof dateInput === 'string') {
    return dateInput.split('T')[0];
  }
  const dateObj = dateInput instanceof Date ? dateInput : new Date(dateInput);
  if (isNaN(dateObj.getTime())) return '';

  const year = dateObj.getFullYear();
  const month = String(dateObj.getMonth() + 1).padStart(2, '0');
  const day = String(dateObj.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

/**
 * Formatea una fecha 'YYYY-MM-DD' (o con hora, ej. ISO) como 'DD/MM/YYYY'.
 *
 * @param {string|null} dateInput
 * @returns {string} Fecha en formato 'DD/MM/YYYY' o '' si es invalida.
 */
export function formatDateDMY(dateInput) {
  if (!dateInput) return '';
  const [year, month, day] = dateInput.slice(0, 10).split('-');
  if (!year || !month || !day) return '';
  return `${day}/${month}/${year}`;
}

const MONTH_NAMES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

/**
 * Formatea un 'member_since' tipo 'YYYY-MM-DD' como "mes año" en español (ej. "septiembre 2026").
 *
 * @param {string|null} memberSince
 * @returns {string} Etiqueta formateada o '' si no hay fecha.
 */
export function formatMemberSince(memberSince) {
  if (!memberSince) return '';
  const [year, month] = memberSince.split('-');
  const monthName = MONTH_NAMES[parseInt(month, 10) - 1] || '';
  return monthName ? `${monthName} ${year}` : '';
}
