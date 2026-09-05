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
