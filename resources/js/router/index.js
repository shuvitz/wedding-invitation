import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '../stores/auth.store';

// Lazy-loaded page components
const LoginPage = () => import('../pages/LoginPage.vue');
const DashboardPage = () => import('../pages/DashboardPage.vue');
const UsersPage = () => import('../pages/UsersPage.vue');
const SettingsPage = () => import('../pages/SettingsPage.vue');
const NavigationPage = () => import('../pages/NavigationPage.vue');
const MediaPage = () => import('../pages/MediaPage.vue');
const PagesPage = () => import('../pages/PagesPage.vue');
const PageEditorPage = () => import('../pages/PageEditorPage.vue');
const FormSubmissionsPage = () => import('../pages/FormSubmissionsPage.vue');
const GuestsPage = () => import('../pages/GuestsPage.vue');

// Layout
const MainLayout = () => import('../layouts/MainLayout.vue');

const routes = [
    {
        path: '/login',
        name: 'login',
        component: LoginPage,
        meta: { guest: true },
    },
    {
        path: '/',
        component: MainLayout,
        meta: { auth: true },
        children: [
            {
                path: '',
                redirect: '/dashboard',
            },
            {
                path: '/dashboard',
                name: 'dashboard',
                component: DashboardPage,
            },
            {
                path: '/admin/users',
                name: 'admin.users',
                component: UsersPage,
                meta: { adminOnly: true },
            },
            {
                // Viewable by admin and editor alike — only mutation is
                // admin-only, enforced server-side and reflected in the UI.
                path: '/admin/settings',
                name: 'admin.settings',
                component: SettingsPage,
            },
            {
                path: '/admin/navigation',
                name: 'admin.navigation',
                component: NavigationPage,
            },
            {
                // Viewable by admin and editor alike — same rationale as Settings/Navigation.
                path: '/admin/media',
                name: 'admin.media',
                component: MediaPage,
            },
            {
                // Editors can view/create/edit-draft pages — PagePolicy
                // enforces the finer-grained rules server-side.
                path: '/admin/pages',
                name: 'admin.pages',
                component: PagesPage,
            },
            {
                path: '/admin/pages/new',
                name: 'admin.pages.new',
                component: PageEditorPage,
            },
            {
                path: '/admin/pages/:id/edit',
                name: 'admin.pages.edit',
                component: PageEditorPage,
            },
            {
                // Visitor-entered personal data — admin only (FormSubmissionPolicy).
                path: '/admin/pages/:id/submissions',
                name: 'admin.pages.submissions',
                component: FormSubmissionsPage,
                meta: { adminOnly: true },
            },
            {
                // Names and phone numbers — admin only (GuestPolicy).
                path: '/admin/guests',
                name: 'admin.guests',
                component: GuestsPage,
                meta: { adminOnly: true },
            },
        ],
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

/**
 * Navigation guard — protect auth routes, redirect guests.
 */
router.beforeEach(async (to) => {
    const auth = useAuthStore();

    // On first navigation, try to fetch the user if we don't know auth state yet
    if (auth.user === null && !auth.isLoading) {
        try {
            await auth.fetchUser();
        } catch {
            // not authenticated — that's fine
        }
    }

    // Trying to access an auth-required route while not authenticated
    if (to.meta.auth && !auth.isAuthenticated) {
        return { name: 'login' };
    }

    // Trying to access a guest-only route while authenticated
    if (to.meta.guest && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }

    // Admin-only route, non-admin user — authorization is still enforced
    // server-side (UserPolicy) on every request; this only avoids showing
    // a page whose API calls would all 403.
    if (to.meta.adminOnly && auth.user?.role !== 'admin') {
        return { name: 'dashboard' };
    }
});

export default router;
