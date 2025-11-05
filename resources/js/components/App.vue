<template>
  <router-view />
</template>

<script setup>
import { onMounted } from 'vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();

onMounted(() => {
  // Verificar autenticación al cargar la aplicación
  authStore.checkAuth();
});
</script>

<style>
/* Estilos globales si son necesarios */
</style>
