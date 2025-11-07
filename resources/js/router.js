import { createRouter, createWebHistory } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const routes = [
  {
    path: '/',
    redirect: '/dashboard'
  },
  {
    path: '/dashboard',
    name: 'dashboard',
    component: () => import('@/pages/DashboardPage.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/test',
    name: 'test',
    component: () => import('@/pages/TestPage.vue'),
    meta: { requiresAuth: true }
  }
  ,
  {
    path: '/select-role',
    name: 'select-role',
    component: () => import('@/pages/SelectRole.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/caja',
    name: 'caja',
    component: () => import('@/pages/CajaPage.vue'),
    meta: { requiresAuth: true }
  },
  {
    path: '/caja/pedido/:id',
    name: 'pedido',
    component: () => import('@/pages/PedidoPage.vue'),
    meta: { requiresAuth: true }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Navigation guards
router.beforeEach(async (to, from, next) => {
  const authStore = useAuthStore();
  
  if (to.meta.requiresAuth) {
    // Check authentication status before deciding
    if (authStore.user === null) {
      await authStore.checkAuth();
    }
    
    if (!authStore.isAuthenticated) {
      // Only redirect if truly not authenticated
      window.location.href = '/login';
      return;
    }
  }
  
  next();
});

export default router;
