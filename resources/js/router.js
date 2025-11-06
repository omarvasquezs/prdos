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
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes
});

// Navigation guards
router.beforeEach((to, from, next) => {
  const authStore = useAuthStore();
  
  if (to.meta.requiresAuth && !authStore.isAuthenticated) {
    // Redirigir a login si la ruta requiere autenticación
    window.location.href = '/login';
  } else {
    next();
  }
});

export default router;
