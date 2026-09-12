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

import stickerFounder from '@/assets/stickers/sticker_01.png';
import stickerStreak from '@/assets/stickers/sticker_02.png';
import stickerPages from '@/assets/stickers/sticker_03.png';
import stickerDaysRead from '@/assets/stickers/sticker_04.png';
import stickerBooksFinished from '@/assets/stickers/sticker_05.png';
import stickerFollowing from '@/assets/stickers/sticker_06.png';
import stickerFollowers from '@/assets/stickers/sticker_07.png';
import stickerMutual from '@/assets/stickers/sticker_08.png';
import stickerNudgeSent from '@/assets/stickers/sticker_09.png';
import stickerNudgeReceived from '@/assets/stickers/sticker_10.png';
import stickerReactionLoved from '@/assets/stickers/sticker_11.png';
import stickerReactionThoughtful from '@/assets/stickers/sticker_12.png';
import stickerReactionPeaceful from '@/assets/stickers/sticker_13.png';
import stickerReactionChallenged from '@/assets/stickers/sticker_14.png';
import stickerReactionMoved from '@/assets/stickers/sticker_15.png';

export const BADGE_GROUPS = [
  {
    category: 'founder',
    group: 'founder',
    image: stickerFounder,
    description: 'Te uniste a Libringo en sus primeros días. ¡Gracias por confiar desde el principio!',
    items: [
      { id: 'founder', threshold: 1, label: 'Fundador' },
    ],
  },
  {
    category: 'streak',
    group: 'streak',
    image: stickerStreak,
    description: 'Días seguidos leyendo, sin perder la racha.',
    items: [
      { id: 'streak_1', threshold: 1, label: 'Primera lectura' },
      { id: 'streak_7', threshold: 7, label: 'Semana completa' },
      { id: 'streak_30', threshold: 30, label: 'Un mes de racha' },
      { id: 'streak_100', threshold: 100, label: 'Imparable' },
      { id: 'streak_365', threshold: 365, label: 'Leyenda' },
      { id: 'streak_730', threshold: 730, label: 'Dos años de racha' },
    ],
  },
  {
    category: 'pages',
    group: 'reading',
    image: stickerPages,
    description: 'Páginas o capítulos leídos en total.',
    items: [
      { id: 'pages_100', threshold: 100, label: 'Lector dedicado' },
      { id: 'pages_500', threshold: 500, label: 'Devorador de libros' },
      { id: 'pages_1000', threshold: 1000, label: 'Lector veterano' },
      { id: 'pages_2500', threshold: 2500, label: 'Erudito' },
      { id: 'pages_5000', threshold: 5000, label: 'Maestro lector' },
    ],
  },
  {
    category: 'days_read',
    group: 'reading',
    image: stickerDaysRead,
    description: 'Días leídos en total (no tienen que ser seguidos).',
    items: [
      { id: 'days_read_50', threshold: 50, label: '50 días leídos' },
      { id: 'days_read_100', threshold: 100, label: '100 días leídos' },
      { id: 'days_read_365', threshold: 365, label: 'Un año leído' },
      { id: 'days_read_730', threshold: 730, label: 'Dos años leídos' },
    ],
  },
  {
    category: 'books_finished',
    group: 'reading',
    image: stickerBooksFinished,
    description: 'Libros terminados en Libringo.',
    items: [
      { id: 'books_finished_1', threshold: 1, label: 'Primer libro terminado' },
      { id: 'books_finished_5', threshold: 5, label: '5 libros terminados' },
      { id: 'books_finished_10', threshold: 10, label: '10 libros terminados' },
      { id: 'books_finished_25', threshold: 25, label: '25 libros terminados' },
    ],
  },
  {
    category: 'following',
    group: 'friends',
    image: stickerFollowing,
    description: 'Personas a las que sigues en Libringo.',
    items: [
      { id: 'following_1', threshold: 1, label: 'Primer amigo' },
      { id: 'following_5', threshold: 5, label: 'Sigues a 5' },
      { id: 'following_20', threshold: 20, label: 'Explorador social' },
      { id: 'following_50', threshold: 50, label: 'Gran explorador' },
    ],
  },
  {
    category: 'followers',
    group: 'friends',
    image: stickerFollowers,
    description: 'Personas que te siguen en Libringo.',
    items: [
      { id: 'followers_5', threshold: 5, label: '5 seguidores' },
      { id: 'followers_20', threshold: 20, label: 'Comunidad' },
      { id: 'followers_50', threshold: 50, label: 'Influyente' },
    ],
  },
  {
    category: 'mutual',
    group: 'friends',
    image: stickerMutual,
    description: 'Personas que te siguen y a las que también sigues.',
    items: [
      { id: 'mutual_5', threshold: 5, label: '5 amigos' },
      { id: 'mutual_20', threshold: 20, label: '20 amigos' },
      { id: 'mutual_50', threshold: 50, label: '50 amigos' },
    ],
  },
  {
    category: 'reaction_loved',
    group: 'reaction',
    image: stickerReactionLoved,
    description: 'Lecturas que te encantaron.',
    items: [
      { id: 'reaction_loved_10', threshold: 10, label: 'Lecturas favoritas' },
    ],
  },
  {
    category: 'reaction_thoughtful',
    group: 'reaction',
    image: stickerReactionThoughtful,
    description: 'Lecturas que te hicieron pensar.',
    items: [
      { id: 'reaction_thoughtful_10', threshold: 10, label: 'Pensador' },
    ],
  },
  {
    category: 'reaction_peaceful',
    group: 'reaction',
    image: stickerReactionPeaceful,
    description: 'Lecturas que te dieron paz.',
    items: [
      { id: 'reaction_peaceful_10', threshold: 10, label: 'En paz' },
    ],
  },
  {
    category: 'reaction_challenged',
    group: 'reaction',
    image: stickerReactionChallenged,
    description: 'Lecturas que confrontaron tu vida y hábitos.',
    items: [
      { id: 'reaction_challenged_10', threshold: 10, label: 'Desafiado' },
    ],
  },
  {
    category: 'reaction_moved',
    group: 'reaction',
    image: stickerReactionMoved,
    description: 'Lecturas que te conmovieron.',
    items: [
      { id: 'reaction_moved_10', threshold: 10, label: 'Conmovido' },
    ],
  },
  {
    category: 'nudge_sent',
    group: 'nudge',
    image: stickerNudgeSent,
    description: 'Toques enviados a tus amigos.',
    items: [
      { id: 'nudge_sent_10', threshold: 10, label: 'Motivador' },
      { id: 'nudge_sent_50', threshold: 50, label: 'Superfan' },
      { id: 'nudge_sent_100', threshold: 100, label: 'Incansable' },
    ],
  },
  {
    category: 'nudge_received',
    group: 'nudge',
    image: stickerNudgeReceived,
    description: 'Toques recibidos de tus amigos.',
    items: [
      { id: 'nudge_received_10', threshold: 10, label: 'Popular' },
      { id: 'nudge_received_50', threshold: 50, label: 'Muy popular' },
      { id: 'nudge_received_100', threshold: 100, label: 'Superestrella' },
    ],
  },
];

export const getBadgeById = (id) => {
  for (const badgeGroup of BADGE_GROUPS) {
    const item = badgeGroup.items.find((i) => i.id === id);
    if (item) return { ...item, category: badgeGroup.category, image: badgeGroup.image, description: badgeGroup.description };
  }
  return null;
};

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

