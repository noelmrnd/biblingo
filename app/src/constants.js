import { Capacitor } from '@capacitor/core';

export const IS_DEV = import.meta.env.DEV === true;

export const API_BASE_URL = IS_DEV
  ? `http://${window.location.hostname}:8084/api`
  : 'https://app.biblingo.me/api';

export const GOOGLE_CLIENT_ID = '56637027170-3k9bfjk1rh4vtfs3lm3ev8sp0tgv3aoi.apps.googleusercontent.com';

export const GOOGLE_IOS_CLIENT_ID = '56637027170-5bckf6oali35ir6m2qisr9urm5qknncg.apps.googleusercontent.com';

export const getAppleConfig = (platform) => {
  const isNativeIOS = platform === 'ios';

  return {
    clientId: isNativeIOS ? 'me.biblingo.app' : 'me.biblingo.app.service',
    redirectUrl: isNativeIOS ? '' : `${API_BASE_URL}/auth/apple/callback`,
  };
};

export const APP_CONFIG = {
  name: 'Biblingo',
  prodWebUrl: 'https://biblingo.me',
  prodAppUrl: 'https://app.biblingo.me',
  isDev: IS_DEV,
  isNativeIOS: Capacitor.isNativePlatform() && Capacitor.getPlatform() === 'ios',
  apiBaseUrl: API_BASE_URL,
};

export const READING_REACTIONS = [
  { id: 'loved', emoji: '❤️', label: 'Me encantó', desc: 'Inspiradora y edificante' },
  { id: 'thoughtful', emoji: '💡', label: 'Me puso a pensar', desc: 'Profunda y reflexiva' },
  { id: 'peaceful', emoji: '🕊️', label: 'Me dio paz', desc: 'Tranquila y reconfortante' },
  { id: 'challenged', emoji: '⚡', label: 'Me desafió', desc: 'Confrontó mi vida y hábitos' },
  { id: 'saddened', emoji: '🥺', label: 'Me entristeció', desc: 'Sensible o conmovedora' },
];

export const getReactionById = (id) => READING_REACTIONS.find((r) => r.id === id) || null;

// Hitos de racha: dias exactos en que se celebra (streak_count sube de a 1 por dia,
// asi que un match exacto alcanza, no hace falta >=).
export const STREAK_MILESTONES = [
  { days: 7, emoji: '🥉', label: '¡Una semana completa!' },
  { days: 30, emoji: '🥈', label: '¡Un mes de racha!' },
  { days: 100, emoji: '🥇', label: '¡100 días! Eres imparable' },
  { days: 365, emoji: '👑', label: '¡Un año leyendo! Leyenda total' },
];

export const getMilestoneForStreak = (streakCount) =>
  STREAK_MILESTONES.find((m) => m.days === streakCount) || null;

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

