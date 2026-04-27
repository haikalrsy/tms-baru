import { createRouter, createWebHistory } from 'vue-router'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    // ── Root ────────────────────────────────────────────────
    {
      path: '/',
      component: () => import('@/views/SplashView.vue'),
    },

    // ── Auth ────────────────────────────────────────────────
    {
      path: '/login',
      component: () => import('@/views/LoginView.vue'),
    },
    {
      path: '/register',
      component: () => import('@/views/RegisterView.vue'),
    },
    {
      path: '/verify-email',
      component: () => import('@/views/VerifyEmailView.vue'),
    },
    {
      path: '/pending-approval',
      component: () => import('@/views/PendingApprovalView.vue'),
    },
    {
      path: '/auth/callback',
      component: () => import('@/views/AuthCallbackView.vue'),
    },
    {
      path: '/auth/google/complete',
      component: () => import('@/views/GoogleCompleteView.vue'),
    },

    // ── Admin ────────────────────────────────────────────────
    {
      path: '/admin',
      component: () => import('@/views/admin/AdminLayoutView.vue'),
      children: [
        { path: '',           component: () => import('@/views/admin/AdminOverviewView.vue') },
        { path: 'accounts',   component: () => import('@/views/admin/AccountsView.vue') },
        { path: 'deliveries', component: () => import('@/views/admin/DeliveriesView.vue') },
        { path: 'drivers',    component: () => import('@/views/admin/DriversView.vue') },
        { path: 'vehicles',   component: () => import('@/views/admin/VehiclesView.vue') },
        { path: 'warehouses', component: () => import('@/views/admin/WarehousesView.vue') },
        { path: 'stock',      component: () => import('@/views/admin/StockView.vue') },
        { path: 'notes',      component: () => import('@/views/admin/tms/DeliveryNotesView.vue') },
      ],
    },

    // ── Fallback ─────────────────────────────────────────────
    { path: '/:pathMatch(.*)*', redirect: '/login' },
  ],
})

export default router
export { router } 