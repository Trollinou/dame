import { createRouter, createWebHashHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import TabsPage from '../views/TabsPage.vue';
import LoginPage from '../views/LoginPage.vue';
import MembersPage from '../views/MembersPage.vue';
import ContactsPage from '../views/ContactsPage.vue';
import AgendaPage from '../views/AgendaPage.vue';
import SondagesPage from '../views/SondagesPage.vue';
import MessagesPage from '../views/MessagesPage.vue';
import TournamentPage from '../views/TournamentPage.vue';
import GenericPage from '../views/GenericPage.vue';
import { useAuthStore } from '@/stores/auth';

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/tabs/home'
  },
  {
    path: '/login',
    redirect: '/tabs/login'
  },
  {
    path: '/tabs/',
    component: TabsPage,
    children: [
      {
        path: '',
        redirect: '/tabs/home'
      },
      // Routes Publiques
      {
        path: 'home',
        component: () => import('../views/PublicHomePage.vue')
      },
      {
        path: 'news',
        component: () => import('../views/NewsPage.vue')
      },
      {
        path: 'news/:id',
        name: 'NewsDetail',
        component: () => import('../views/NewsDetailPage.vue')
      },
      {
        path: 'agenda',
        component: AgendaPage
      },
      {
        path: 'agenda/:id',
        name: 'AgendaDetail',
        component: () => import('@/views/AgendaDetailPage.vue')
      },
      {
        path: 'tournoi',
        component: TournamentPage
      },
      {
        path: 'admin/survey',
        component: SondagesPage,
        meta: { requiresAuth: true }
      },
      {
        path: 'admin/survey/:id',
        name: 'SondageDetail',
        component: () => import('@/views/SondageDetailPage.vue')
      },
      {
        path: 'page/:id',
        name: 'GenericPage',
        component: GenericPage
      },
      {
        path: 'login',
        component: LoginPage
      },
      // Routes Privées (Admin)
      {
        path: 'admin/dashboard',
        component: () => import('../views/HomePage.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'admin/members',
        component: MembersPage,
        meta: { requiresAuth: true }
      },
      {
        path: 'admin/members/:id',
        name: 'MemberDetail',
        component: () => import('@/views/MemberDetailPage.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'admin/contact',
        component: ContactsPage,
        meta: { requiresAuth: true }
      },
      {
        path: 'admin/contact/:id',
        name: 'ContactDetail',
        component: () => import('@/views/ContactDetailPage.vue'),
        meta: { requiresAuth: true }
      },
      {
        path: 'admin/message',
        component: MessagesPage,
        meta: { requiresAuth: true }
      },
      {
        path: 'admin/message/:id',
        name: 'MessageDetail',
        component: () => import('@/views/MessageDetailPage.vue'),
        meta: { requiresAuth: true }
      },
    ]
  }
]

const router = createRouter({
  history: createWebHashHistory(import.meta.env.BASE_URL),
  routes
})

// Navigation Guard
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    next('/tabs/login');
  } else {
    next();
  }
});

export default router
