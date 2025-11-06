<template>
  <div class="min-vh-100 bg-light">
    <!-- Header/Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom">
      <div class="container-fluid">
  <span class="navbar-brand mb-0 h1">Polleria P'rdos</span>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto align-items-center">
            <li class="nav-item">
              <router-link 
                to="/dashboard"
                class="nav-link"
                :class="{ 'active fw-bold': $route.path === '/dashboard' }"
              >
                Dashboard
              </router-link>
            </li>
            <li class="nav-item">
              <router-link 
                to="/test"
                class="nav-link"
                :class="{ 'active fw-bold': $route.path === '/test' }"
              >
                Página Test
              </router-link>
            </li>
            <li class="nav-item ms-3 border-start ps-3">
              <span class="navbar-text me-3">
                {{ authStore.user?.name || authStore.user?.email }}
              </span>
              <button
                @click="handleLogout"
                class="btn btn-danger btn-sm"
              >
                Cerrar Sesión
              </button>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Main Content -->
    <main class="container-fluid py-4">
      <slot />
    </main>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';

const router = useRouter();
const authStore = useAuthStore();

const handleLogout = async () => {
  await authStore.logout();
  window.location.href = '/login';
};
</script>

<style scoped>
.nav-link.active {
  color: #0d6efd !important;
}
</style>
