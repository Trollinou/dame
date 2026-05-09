import { createRouter, createWebHashHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import TabsPage from '../views/TabsPage.vue';
import LoginPage from '../views/LoginPage.vue';
import MembersPage from '../views/MembersPage.vue';
import ContactsPage from '../views/ContactsPage.vue';
import AgendaPage from '../views/AgendaPage.vue';
import SondagesPage from '../views/SondagesPage.vue';
import MessagesPage from '../views/MessagesPage.vue';

const routes: Array<RouteRecordRaw> = [
  {
    path: '/',
    redirect: '/tabs/home'
  },
  {
    path: '/login',
    component: LoginPage
  },
  {
    path: '/tabs/',
    component: TabsPage,
    children: [
      {
        path: '',
        redirect: '/tabs/home'
      },
      {
        path: 'home',
        component: () => import('../views/HomePage.vue')
      },
      {
        path: 'members',
        component: MembersPage
      },
      {
        path: 'members/:id',
        name: 'MemberDetail',
        component: () => import('@/views/MemberDetailPage.vue')
      },
      {
        path: 'contact',
        component: ContactsPage
      },
      {
        path: 'agenda',
        component: AgendaPage
      },
      {
        path: 'survey',
        component: SondagesPage
      },
      {
        path: 'message',
        component: MessagesPage
      }
    ]
  }
]

const router = createRouter({
  history: createWebHashHistory(import.meta.env.BASE_URL),
  routes
})

export default router
