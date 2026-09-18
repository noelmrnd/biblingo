// Algunos WebView de Android caen a 'UTC' cuando ICU no logra resolver el tz
// del dispositivo a tiempo (arranque en frio, tz aun no inicializado por el
// SO). Si Intl dice 'UTC' pero el offset real del dispositivo no es 0, sabemos
// que esta mintiendo: mandar 'UTC' igual rompe el corte de dia de la racha
// (medianoche UTC en vez de la hora local). En ese caso se arma un fallback
// 'Etc/GMT+-N' a partir del offset crudo — PHP DateTimeZone lo acepta y calcula
// bien el dia local, aunque no sea el nombre IANA exacto de la ciudad.
export const getDeviceTimezone = () => {
  const detected = Intl.DateTimeFormat().resolvedOptions().timeZone || 'UTC';
  const offsetMinutes = new Date().getTimezoneOffset();

  if (detected !== 'UTC' || offsetMinutes === 0) return detected;

  // getTimezoneOffset() es minutos a RESTAR de la hora local para llegar a UTC
  // (positivo al oeste de UTC). Etc/GMT usa signo invertido por convencion POSIX.
  const offsetHours = -offsetMinutes / 60;
  if (!Number.isInteger(offsetHours)) return detected;

  const sign = offsetHours >= 0 ? '+' : '-';
  return `Etc/GMT${sign}${Math.abs(offsetHours)}`;
};
