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

