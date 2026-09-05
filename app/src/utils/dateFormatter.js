/**
 * Helper para formatear fechas a DD/MM/YYYY.
 * 
 * @param {string|Date|number|null} dateInput - Fecha en formato 'YYYY-MM-DD', Objeto Date o Timestamp.
 * @param {string} fallbackText - Texto a mostrar si la fecha es nula o inválida.
 * @returns {string} Fecha formateada en DD/MM/YYYY.
 */
export function formatDateDDMMYYYY(dateInput, fallbackText = 'Sin registro') {
  if (!dateInput) return fallbackText;

  try {
    let day, month, year;

    if (typeof dateInput === 'string') {
      // Si es formato YYYY-MM-DD
      const parts = dateInput.split('T')[0].split('-');
      if (parts.length === 3) {
        year = parts[0];
        month = parts[1].padStart(2, '0');
        day = parts[2].padStart(2, '0');
        return `${day}/${month}/${year}`;
      }
    }

    const dateObj = new Date(dateInput);
    if (isNaN(dateObj.getTime())) return fallbackText;

    day = String(dateObj.getDate()).padStart(2, '0');
    month = String(dateObj.getMonth() + 1).padStart(2, '0');
    year = dateObj.getFullYear();

    return `${day}/${month}/${year}`;
  } catch (e) {
    return fallbackText;
  }
}

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
 * Formatea una fecha indicando 'Hoy', 'Ayer' o la fecha en DD/MM/YYYY.
 */
export function formatFriendlyDate(dateInput) {
  if (!dateInput) return 'Sin racha';

  const todayStr = toLocalDateString(new Date());
  const yesterdayDate = new Date();
  yesterdayDate.setDate(yesterdayDate.getDate() - 1);
  const yesterdayStr = toLocalDateString(yesterdayDate);

  const dateStr = toLocalDateString(dateInput);

  if (dateStr === todayStr) return 'Hoy';
  if (dateStr === yesterdayStr) return 'Ayer';

  return formatDateDDMMYYYY(dateInput);
}
