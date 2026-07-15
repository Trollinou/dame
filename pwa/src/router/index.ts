import { createRouter, createWebHashHistory } from '@ionic/vue-router';
import { RouteRecordRaw } from 'vue-router';
import TabsPage from '../views/TabsPage.vue';
import LoginPage from '../views/LoginPage.vue';
import MembersPage from '../views/MembersPage.vue';
import ContactsPage from '../views/ContactsPage.vue';
import AgendaPage from '../views/AgendaPage.vue';
import BenevolatPage from '../views/BenevolatPage.vue';
import MessagesPage from '../views/MessagesPage.vue';
import AdminLayout from '../views/AdminLayout.vue';
const TournamentPage = () => import( '../views/TournamentPage.vue' );
const GenericPage = () => import( '../views/GenericPage.vue' );
import { useAuthStore } from '@/stores/auth';

const routes: Array< RouteRecordRaw > = [
	{
		path: '/',
		redirect: '/tabs/home',
	},
	{
		path: '/login',
		component: LoginPage,
	},
	{
		path: '/tabs/login',
		redirect: '/login',
	},
	// Routes principales de la navigation PWA (Tabs)
	{
		path: '/tabs/',
		component: TabsPage,
		children: [
			{
				path: '',
				redirect: '/tabs/home',
			},
			{
				path: 'home',
				component: () => import( '../views/PublicHomePage.vue' ),
			},
			{
				path: 'agenda',
				component: AgendaPage,
			},
			{
				path: 'apprentissage',
				component: () => import( '../views/ApprentissageHubPage.vue' ),
			},
			{
				path: 'profil',
				component: () => import( '../views/ProfilePage.vue' ),
			},
		],
	},
	// Nouveau groupe de routes d'administration protégé avec un layout dédié
	{
		path: '/admin',
		component: AdminLayout,
		meta: { requiresAuth: true, requiresAdmin: true },
		children: [
			{
				path: '',
				redirect: '/admin/dashboard',
			},
			{
				path: 'dashboard',
				component: () => import( '../views/HomePage.vue' ),
			},
			{
				path: 'members',
				component: MembersPage,
			},
			{
				path: 'members/:id',
				name: 'MemberDetail',
				component: () => import( '@/views/MemberDetailPage.vue' ),
			},
			{
				path: 'contact',
				component: ContactsPage,
			},
			{
				path: 'contact/:id',
				name: 'ContactDetail',
				component: () => import( '@/views/ContactDetailPage.vue' ),
			},
			{
				path: 'message',
				component: MessagesPage,
			},
			{
				path: 'message/:id',
				name: 'MessageDetail',
				component: () => import( '@/views/MessageDetailPage.vue' ),
			},
			{
				path: 'benevolat',
				component: BenevolatPage,
			},
			{
				path: 'benevolat/:id',
				name: 'BenevolatDetail',
				component: () => import( '@/views/BenevolatDetailPage.vue' ),
			},
		],
	},
	// Routes publiques secondaires hors Tabs
	{
		path: '/news',
		component: () => import( '../views/NewsPage.vue' ),
	},
	{
		path: '/news/:id',
		name: 'NewsDetail',
		component: () => import( '../views/NewsDetailPage.vue' ),
	},
	{
		path: '/agenda/:id',
		name: 'AgendaDetail',
		component: () => import( '@/views/AgendaDetailPage.vue' ),
	},
	{
		path: '/tournoi',
		component: TournamentPage,
	},
	{
		path: '/benevolat',
		component: BenevolatPage,
	},
	{
		path: '/benevolat/participation/:id',
		name: 'BenevolatVote',
		meta: { requiresAuth: true },
		component: () => import( '@/views/BenevolatVotePage.vue' ),
	},
	{
		path: '/page/:id',
		name: 'GenericPage',
		component: GenericPage,
	},
	{
		path: '/register',
		component: () => import( '../views/RegisterPage.vue' ),
	},
	{
		path: '/pre-inscription',
		component: () => import( '../views/PreInscriptionPage.vue' ),
	},
	{
		path: '/select-person',
		component: () => import( '../views/SelectPersonPage.vue' ),
		meta: { requiresAuth: true },
	},
	{
		path: '/contenu/:id',
		component: () => import( '../views/ContenuPage.vue' ),
		meta: { requiresAuth: true, requiresAdherent: true },
	},
	{
		path: '/cours/:id',
		component: () => import( '../views/CoursPage.vue' ),
		meta: { requiresAuth: true, requiresAdherent: true },
	},
];

const router = createRouter( {
	history: createWebHashHistory( import.meta.env.BASE_URL ),
	routes,
} );

// Navigation Guard (Vue Router 4 style)
router.beforeEach( ( to ) => {
	const authStore = useAuthStore();

	// 1. Vérification de l'authentification de base
	if ( to.meta.requiresAuth && ! authStore.isAuthenticated ) {
		return {
			path: '/login',
			query: {
				message: 'Vous devez être connecté pour accéder à cette page.',
			},
		};
	}

	// 2. Vérification des droits d'administration
	if ( to.meta.requiresAdmin && ! authStore.isAdmin ) {
		return {
			path: '/tabs/home',
			query: { message: 'Accès refusé : Droits insuffisants.' },
		};
	}

	// 2b. Vérification adhérent
	if ( to.meta.requiresAdherent && ! authStore.isAdherent ) {
		return {
			path: '/tabs/home',
			query: { message: 'Accès réservé aux adhérents.' },
		};
	}

	// 3. Vérification de l'activation du module de jeu (requiert ROI)
	const chessRoutes = [ '/tabs/play', '/tabs/analysis' ];
	if ( chessRoutes.includes( to.path ) && ! authStore.isRoiActive ) {
		return {
			path: '/tabs/home',
		};
	}

	return true;
} );

export default router;
