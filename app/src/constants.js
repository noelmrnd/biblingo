import { Capacitor } from '@capacitor/core';

export const IS_DEV = import.meta.env.DEV === true;

export const API_BASE_URL = IS_DEV
  ? `http://${window.location.hostname}:8084/api`
  : 'https://app.libringo.com/api';

export const GOOGLE_CLIENT_ID = '18709132885-b03ocies3hbsl9rffeutm4rjjmvl9sna.apps.googleusercontent.com';

export const GOOGLE_IOS_CLIENT_ID = '18709132885-mpnr2j14sejtat54rknuecv5b7hnpm8j.apps.googleusercontent.com';

export const getAppleConfig = (platform) => {
  const isNativeIOS = platform === 'ios';

  return {
    clientId: isNativeIOS ? 'com.libringo.app' : 'com.libringo.app.service',
    redirectUrl: isNativeIOS ? '' : `${API_BASE_URL}/auth/apple/callback`,
  };
};

export const APP_CONFIG = {
  name: 'Libringo',
  appDomain: 'app.libringo.com',
  prodWebUrl: 'https://libringo.com',
  prodAppUrl: 'https://app.libringo.com',
  isDev: IS_DEV,
  isNativeIOS: Capacitor.isNativePlatform() && Capacitor.getPlatform() === 'ios',
  apiBaseUrl: API_BASE_URL,
};

export const READING_REACTIONS = [
  { id: 'loved', emoji: '❤️', label: 'Me encantó', desc: 'Inspiradora y edificante' },
  { id: 'thoughtful', emoji: '💡', label: 'Me puso a pensar', desc: 'Profunda y reflexiva' },
  { id: 'peaceful', emoji: '🕊️', label: 'Me dio paz', desc: 'Tranquila y reconfortante' },
  { id: 'challenged', emoji: '⚡️', label: 'Me desafió', desc: 'Confrontó mi vida y hábitos' },
  { id: 'moved', emoji: '🥺', label: 'Me conmovió', desc: 'Sensible o emotiva' },
];

export const getReactionById = (id) => READING_REACTIONS.find((r) => r.id === id) || null;

// Catalogo de medallas: mismo catalogo que BadgeEntity::CATALOG en el backend
// (duplicado a proposito, igual que READING_REACTIONS vs VALID_REACTIONS).
// category+threshold(+reaction) definen CUANDO se otorga (eso lo decide el
// backend); emoji/label son solo presentacion. Agregar un tipo de medalla
// nuevo es agregar una entrada aca + su espejo en BadgeEntity::CATALOG.
// label: corto, para chips/circulos. description: se muestra solo al abrir el
// detalle (modal), puede ser mas largo/explicativo.
export const BADGES = [
  { id: 'founder', category: 'founder', threshold: 1, emoji: '🚀', label: 'Fundador', description: 'Te uniste a Libringo en sus primeros días. ¡Gracias por confiar desde el principio!' },
  { id: 'streak_1', category: 'streak', threshold: 1, emoji: '📖', label: 'Primera lectura', description: '¡Registraste tu primera lectura en Libringo!' },
  { id: 'streak_7', category: 'streak', threshold: 7, emoji: '🥉', label: 'Semana completa', description: 'Completaste 7 días seguidos de racha.' },
  { id: 'streak_30', category: 'streak', threshold: 30, emoji: '🥈', label: 'Un mes de racha', description: '30 días seguidos leyendo. ¡Constancia total!' },
  { id: 'streak_100', category: 'streak', threshold: 100, emoji: '🥇', label: 'Imparable', description: '100 días de racha. Pocos llegan tan lejos.' },
  { id: 'streak_365', category: 'streak', threshold: 365, emoji: '👑', label: 'Leyenda', description: 'Un año entero leyendo todos los días.' },
  { id: 'streak_730', category: 'streak', threshold: 730, emoji: '💎', label: 'Dos años de racha', description: 'Dos años enteros leyendo todos los días.' },
  { id: 'following_1', category: 'following', threshold: 1, emoji: '👋', label: 'Primer amigo', description: 'Seguiste a tu primera persona en Libringo.' },
  { id: 'following_5', category: 'following', threshold: 5, emoji: '🤝', label: 'Sigues a 5', description: 'Ya sigues a 5 personas en Libringo.' },
  { id: 'following_20', category: 'following', threshold: 20, emoji: '🧭', label: 'Explorador social', description: 'Sigues a 20 personas en Libringo.' },
  { id: 'following_50', category: 'following', threshold: 50, emoji: '🌐', label: 'Gran explorador', description: 'Sigues a 50 personas en Libringo.' },
  { id: 'followers_5', category: 'followers', threshold: 5, emoji: '⭐', label: '5 seguidores', description: '5 personas te siguen.' },
  { id: 'followers_20', category: 'followers', threshold: 20, emoji: '🎉', label: 'Comunidad', description: '20 personas te siguen.' },
  { id: 'followers_50', category: 'followers', threshold: 50, emoji: '💫', label: 'Influyente', description: '50 personas te siguen.' },
  { id: 'reaction_loved_10', category: 'reaction', threshold: 10, reaction: 'loved', emoji: '❤️', label: 'Lecturas favoritas', description: '10 lecturas que te encantaron.' },
  { id: 'reaction_thoughtful_10', category: 'reaction', threshold: 10, reaction: 'thoughtful', emoji: '💡', label: 'Pensador', description: '10 lecturas que te hicieron pensar.' },
  { id: 'reaction_peaceful_10', category: 'reaction', threshold: 10, reaction: 'peaceful', emoji: '🕊️', label: 'En paz', description: '10 lecturas que te dieron paz.' },
  { id: 'reaction_challenged_10', category: 'reaction', threshold: 10, reaction: 'challenged', emoji: '⚡️', label: 'Desafiado', description: '10 lecturas que confrontaron tu vida y hábitos.' },
  { id: 'reaction_moved_10', category: 'reaction', threshold: 10, reaction: 'moved', emoji: '🥺', label: 'Conmovido', description: '10 lecturas que te conmovieron.' },
  { id: 'days_read_50', category: 'days_read', threshold: 50, emoji: '📚', label: '50 días leídos', description: 'Leíste 50 días en total (no tienen que ser seguidos).' },
  { id: 'days_read_100', category: 'days_read', threshold: 100, emoji: '📖', label: '100 días leídos', description: 'Leíste 100 días en total (no tienen que ser seguidos).' },
  { id: 'days_read_365', category: 'days_read', threshold: 365, emoji: '🏛️', label: 'Un año leído', description: 'Un año completo de días leídos acumulados.' },
  { id: 'days_read_730', category: 'days_read', threshold: 730, emoji: '🗻', label: 'Dos años leídos', description: 'Dos años completos de días leídos acumulados.' },
  { id: 'mutual_5', category: 'mutual', threshold: 5, emoji: '💞', label: '5 amigos', description: '5 personas que te siguen y a las que también sigues.' },
  { id: 'mutual_20', category: 'mutual', threshold: 20, emoji: '💘', label: '20 amigos', description: '20 personas que te siguen y a las que también sigues.' },
  { id: 'mutual_50', category: 'mutual', threshold: 50, emoji: '💝', label: '50 amigos', description: '50 personas que te siguen y a las que también sigues.' },
  { id: 'nudge_sent_10', category: 'nudge_sent', threshold: 10, emoji: '🔔', label: 'Motivador', description: 'Enviaste 10 toques a tus amigos.' },
  { id: 'nudge_sent_50', category: 'nudge_sent', threshold: 50, emoji: '📯', label: 'Superfan', description: 'Enviaste 50 toques a tus amigos.' },
  { id: 'nudge_sent_100', category: 'nudge_sent', threshold: 100, emoji: '🚀', label: 'Incansable', description: 'Enviaste 100 toques a tus amigos.' },
  { id: 'nudge_received_10', category: 'nudge_received', threshold: 10, emoji: '📣', label: 'Popular', description: 'Recibiste 10 toques de tus amigos.' },
  { id: 'nudge_received_50', category: 'nudge_received', threshold: 50, emoji: '🔥', label: 'Muy popular', description: 'Recibiste 50 toques de tus amigos.' },
  { id: 'nudge_received_100', category: 'nudge_received', threshold: 100, emoji: '👑', label: 'Superestrella', description: 'Recibiste 100 toques de tus amigos.' },
  { id: 'pages_100', category: 'pages', threshold: 100, emoji: '📄', label: 'Lector dedicado', description: '100 páginas o capítulos leídos en total.' },
  { id: 'pages_500', category: 'pages', threshold: 500, emoji: '📚', label: 'Devorador de libros', description: '500 páginas o capítulos leídos en total.' },
  { id: 'pages_1000', category: 'pages', threshold: 1000, emoji: '📖', label: 'Lector veterano', description: '1000 páginas o capítulos leídos en total.' },
  { id: 'pages_2500', category: 'pages', threshold: 2500, emoji: '🎓', label: 'Erudito', description: '2500 páginas o capítulos leídos en total.' },
  { id: 'pages_5000', category: 'pages', threshold: 5000, emoji: '🌟', label: 'Maestro lector', description: '5000 páginas o capítulos leídos en total.' },
  { id: 'books_finished_1', category: 'books_finished', threshold: 1, emoji: '✅', label: 'Primer libro terminado', description: 'Terminaste tu primer libro en Libringo.' },
  { id: 'books_finished_5', category: 'books_finished', threshold: 5, emoji: '🏆', label: '5 libros terminados', description: 'Terminaste 5 libros en Libringo.' },
  { id: 'books_finished_10', category: 'books_finished', threshold: 10, emoji: '📗', label: '10 libros terminados', description: 'Terminaste 10 libros en Libringo.' },
  { id: 'books_finished_25', category: 'books_finished', threshold: 25, emoji: '🎖️', label: '25 libros terminados', description: 'Terminaste 25 libros en Libringo.' },
];

// Agrupacion (no afecta el otorgamiento, que sigue usando 'following' y
// 'followers' por separado como categorias reales de BADGES/BadgeEntity):
// solo ordena categorias relacionadas juntas en AchievementsGrid, sin
// mostrar ningun encabezado de seccion.
const BADGE_GROUP_BY_CATEGORY = {
  following: 'friends',
  followers: 'friends',
  mutual: 'friends',
  days_read: 'reading',
  pages: 'reading',
  books_finished: 'reading',
  nudge_sent: 'nudge',
  nudge_received: 'nudge',
};

export const getBadgeGroup = (category) => BADGE_GROUP_BY_CATEGORY[category] || category;

export const getBadgeById = (id) => BADGES.find((b) => b.id === id) || null;

// Escalado visual de la llama de racha en StreakHero segun el hito mas alto alcanzado
// (no exacto como getMilestoneForStreak: aplica a partir del umbral y se mantiene).
export const STREAK_TIERS = [
  { minDays: 365, emoji: '👑', glow: 'rgba(255,215,0,0.9)', sizeClass: 'text-8xl' },
  { minDays: 100, emoji: '🔥', glow: 'rgba(255,80,0,0.9)', sizeClass: 'text-8xl' },
  { minDays: 30, emoji: '🔥', glow: 'rgba(255,150,0,0.85)', sizeClass: 'text-7xl' },
  { minDays: 7, emoji: '🔥', glow: 'rgba(255,150,0,0.8)', sizeClass: 'text-7xl' },
  { minDays: 0, emoji: '🔥', glow: 'rgba(255,150,0,0.8)', sizeClass: 'text-7xl' },
];

export const getStreakTier = (streakCount) =>
  STREAK_TIERS.find((t) => streakCount >= t.minDays);

// Los 66 libros de la Biblia con su numero de capitulos, en orden canonico. Suma
// total = 1189, mismo numero que BookEntity::BIBLE_TOTAL_CHAPTERS en el backend.
// Sirve para agrupar el grid de checkboxes por libro y mapear numero de capitulo
// global (1..1189, el que usa el bitmask) <-> libro + capitulo dentro del libro.
export const BIBLE_CHAPTERS = 1189;
export const BIBLE_BOOKS = [
  { name: 'Génesis', chapters: 50 },
  { name: 'Éxodo', chapters: 40 },
  { name: 'Levítico', chapters: 27 },
  { name: 'Números', chapters: 36 },
  { name: 'Deuteronomio', chapters: 34 },
  { name: 'Josué', chapters: 24 },
  { name: 'Jueces', chapters: 21 },
  { name: 'Rut', chapters: 4 },
  { name: '1 Samuel', chapters: 31 },
  { name: '2 Samuel', chapters: 24 },
  { name: '1 Reyes', chapters: 22 },
  { name: '2 Reyes', chapters: 25 },
  { name: '1 Crónicas', chapters: 29 },
  { name: '2 Crónicas', chapters: 36 },
  { name: 'Esdras', chapters: 10 },
  { name: 'Nehemías', chapters: 13 },
  { name: 'Ester', chapters: 10 },
  { name: 'Job', chapters: 42 },
  { name: 'Salmos', chapters: 150 },
  { name: 'Proverbios', chapters: 31 },
  { name: 'Eclesiastés', chapters: 12 },
  { name: 'Cantar de los Cantares', chapters: 8 },
  { name: 'Isaías', chapters: 66 },
  { name: 'Jeremías', chapters: 52 },
  { name: 'Lamentaciones', chapters: 5 },
  { name: 'Ezequiel', chapters: 48 },
  { name: 'Daniel', chapters: 12 },
  { name: 'Oseas', chapters: 14 },
  { name: 'Joel', chapters: 3 },
  { name: 'Amós', chapters: 9 },
  { name: 'Abdías', chapters: 1 },
  { name: 'Jonás', chapters: 4 },
  { name: 'Miqueas', chapters: 7 },
  { name: 'Nahúm', chapters: 3 },
  { name: 'Habacuc', chapters: 3 },
  { name: 'Sofonías', chapters: 3 },
  { name: 'Ageo', chapters: 2 },
  { name: 'Zacarías', chapters: 14 },
  { name: 'Malaquías', chapters: 4 },
  { name: 'Mateo', chapters: 28 },
  { name: 'Marcos', chapters: 16 },
  { name: 'Lucas', chapters: 24 },
  { name: 'Juan', chapters: 21 },
  { name: 'Hechos', chapters: 28 },
  { name: 'Romanos', chapters: 16 },
  { name: '1 Corintios', chapters: 16 },
  { name: '2 Corintios', chapters: 13 },
  { name: 'Gálatas', chapters: 6 },
  { name: 'Efesios', chapters: 6 },
  { name: 'Filipenses', chapters: 4 },
  { name: 'Colosenses', chapters: 4 },
  { name: '1 Tesalonicenses', chapters: 5 },
  { name: '2 Tesalonicenses', chapters: 3 },
  { name: '1 Timoteo', chapters: 6 },
  { name: '2 Timoteo', chapters: 4 },
  { name: 'Tito', chapters: 3 },
  { name: 'Filemón', chapters: 1 },
  { name: 'Hebreos', chapters: 13 },
  { name: 'Santiago', chapters: 5 },
  { name: '1 Pedro', chapters: 5 },
  { name: '2 Pedro', chapters: 3 },
  { name: '1 Juan', chapters: 5 },
  { name: '2 Juan', chapters: 1 },
  { name: '3 Juan', chapters: 1 },
  { name: 'Judas', chapters: 1 },
  { name: 'Apocalipsis', chapters: 22 },
];

// Anota a cada libro el numero de capitulo GLOBAL (1..1189) en el que empieza,
// para poder convertir entre "capitulo N del libro X" y el indice plano que
// usa el bitmask del backend (BookEntity::orChapters/decodeBitmaskChapters).
let _globalOffset = 0;
export const BIBLE_BOOKS_WITH_OFFSET = BIBLE_BOOKS.map((book) => {
  const withOffset = { ...book, startsAt: _globalOffset + 1 };
  _globalOffset += book.chapters;
  return withOffset;
});

