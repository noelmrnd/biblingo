export const BOOK_TRACKING_MODE = {
  LINEAR: 'linear',
  BITMASK: 'bitmask',
};

// Mismo tope que BookController::MAX_TOTAL_PAGES (anti-abuso: sin esto un
// total_pages absurdo infla el % de avance con solo marcar 1 pagina). Duplicado
// a proposito para validar/deshabilitar el boton antes de golpear la API,
// pero el backend es la autoridad real.
export const MAX_BOOK_TOTAL_PAGES = 2000;

// Frases que activan el modo bitmask (capitulos no lineales, ej. Biblia).
// Match flexible (sin acentos/mayusculas) para no exigirle al usuario escribir
// exacto, ni un titulo exacto ("Biblia"): cubre las formas mas comunes en que
// alguien la nombraria. El front decide el tracking_mode y se lo envia al
// backend tal cual (ver BookController::create) — no se re-deriva del titulo
// en el servidor.
// "escrituras" sola queda afuera a proposito: dispararia con titulos de
// ficcion como "Las escrituras perdidas". Solo frases completas que en la
// practica solo se usan para referirse a la Biblia.
const BITMASK_TITLE_KEYWORDS = [
  'biblia',
  'santas escrituras',
  'sagradas escrituras',
  'escrituras sagradas',
];

const normalizeTitle = (title) => title
  .normalize('NFD').replace(/[̀-ͯ]/g, '')
  .toLowerCase().trim();

export const isBibleTitle = (title) => {
  const normalized = normalizeTitle(title);
  return BITMASK_TITLE_KEYWORDS.some((keyword) => normalized.includes(keyword));
};

export const detectTrackingMode = (title) => isBibleTitle(title)
  ? BOOK_TRACKING_MODE.BITMASK
  : BOOK_TRACKING_MODE.LINEAR;
