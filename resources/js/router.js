import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from './stores/auth';

const Home = () => import('./pages/Home.vue');
const Article = () => import('./pages/Article.vue');
const Listing = () => import('./pages/Listing.vue');
const Auth = () => import('./pages/Auth.vue');
const Profile = () => import('./pages/Profile.vue');
const Admin = () => import('./pages/Admin.vue');
const Forbidden = () => import('./pages/Forbidden.vue');
const NotFound = () => import('./pages/NotFound.vue');

const router = createRouter({
    history: createWebHistory(),
    scrollBehavior: () => ({ top: 0 }),
    routes: [
        { path: '/', component: Home },
        { path: '/articles/:slug', component: Article },
        { path: '/categories/:slug', component: Listing, props: { kind: 'category' } },
        { path: '/tags/:slug', component: Listing, props: { kind: 'tag' } },
        { path: '/search', component: Listing, props: { kind: 'search' } },
        { path: '/breaking-news', component: Listing, props: { kind: 'breaking' } },
        { path: '/most-viewed', component: Listing, props: { kind: 'most' } },
        { path: '/login', component: Auth, props: { mode: 'login' } },
        { path: '/register', component: Auth, props: { mode: 'register' } },
        { path: '/profile', component: Profile },
        { path: '/forbidden', component: Forbidden },
        { path: '/admin', component: Admin, meta: { requiresAdmin: true } },
        { path: '/admin/:section', component: Admin, meta: { requiresAdmin: true } },
        { path: '/:pathMatch(.*)*', component: NotFound },
    ],
});

router.beforeEach(async (to) => {
    if (!to.meta.requiresAdmin) return true;
    const auth = useAuthStore();
    if (!auth.user) await auth.me();
    if (!auth.user) return { path: '/login', query: { redirect: to.fullPath } };
    return auth.isAdmin ? true : { path: '/forbidden', replace: true };
});

export default router;
