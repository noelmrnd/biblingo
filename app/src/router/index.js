import { createRouter, createWebHistory } from 'vue-router';
import DashboardView from '@/views/tabs/DashboardTabView.vue';
import FriendsView from '@/views/tabs/FriendsTabView.vue';
import FriendProfileView from '@/views/FriendProfileView.vue';
import ProfileView from '@/views/tabs/ProfileTabView.vue';
import SettingsView from '@/views/SettingsView.vue';

const routes = [
  // meta.keepAlive: App.vue lo usa para armar el include de <keep-alive> a
  // partir del nombre real del componente, sin repetirlo a mano (evita que
  // un rename del archivo desincronice la lista y rompa el cache en silencio).
  { path: '/', name: 'dashboard', component: DashboardView, meta: { keepAlive: true } },
  { path: '/friends', name: 'friends', component: FriendsView, meta: { keepAlive: true } },
  { path: '/friends/:id', name: 'friend-profile', component: FriendProfileView, props: true },
  { path: '/profile', name: 'profile', component: ProfileView, meta: { keepAlive: true } },
  { path: '/profile/settings', name: 'profile-settings', component: SettingsView },
  // Cualquier ruta no reconocida (ej. /invite/CODIGO en web) cae al dashboard.
  // DeepLinkService ya captura la URL original al cargar el módulo, antes de este redirect.
  { path: '/:pathMatch(.*)*', redirect: '/' }
];

export const router = createRouter({
  history: createWebHistory(),
  routes
});
