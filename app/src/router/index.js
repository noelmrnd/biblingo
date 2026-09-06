import { createRouter, createWebHistory } from 'vue-router';
import DashboardView from '../views/DashboardView.vue';
import FriendsView from '../views/FriendsView.vue';
import FriendProfileView from '../views/FriendProfileView.vue';
import ProfileView from '../views/ProfileView.vue';
import SettingsView from '../views/SettingsView.vue';

const routes = [
  { path: '/', name: 'dashboard', component: DashboardView },
  { path: '/friends', name: 'friends', component: FriendsView },
  { path: '/friends/:id', name: 'friend-profile', component: FriendProfileView, props: true },
  { path: '/profile', name: 'profile', component: ProfileView },
  { path: '/profile/settings', name: 'profile-settings', component: SettingsView },
  // Cualquier ruta no reconocida (ej. /invite/CODIGO en web) cae al dashboard.
  // DeepLinkService ya captura la URL original al cargar el módulo, antes de este redirect.
  { path: '/:pathMatch(.*)*', redirect: '/' }
];

export const router = createRouter({
  history: createWebHistory(),
  routes
});
