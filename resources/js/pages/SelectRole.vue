<template>
  <div class="select-role-container d-flex justify-content-center align-items-center" style="min-height:100vh;">
    <div class="card p-4" style="max-width:520px; width:100%;">
      <h2 class="text-center mb-3">Seleccionar Perfil</h2>
      <p class="text-center text-muted">Elige el perfil con el que quieres entrar.</p>

      <div v-if="loading" class="text-center my-4">Cargando...</div>

      <div v-else class="d-grid gap-3 mt-4">
        <button
          v-for="role in roles"
          :key="role"
          @click="chooseRole(role)"
          class="btn btn-outline-primary btn-lg text-uppercase"
        >
          {{ role }}
        </button>
      </div>

      <div v-if="error" class="alert alert-danger mt-3">{{ error }}</div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'SelectRole',
  data() {
    return {
      roles: [],
      loading: true,
      error: ''
    }
  },
  methods: {
    async load() {
      this.loading = true;
      try {
        const res = await axios.get('/api/user');
        this.roles = res.data?.roles || [];
      } catch (e) {
        this.error = 'No se pudo obtener los perfiles. Intenta iniciar sesión nuevamente.';
      } finally {
        this.loading = false;
      }
    },
    async chooseRole(role) {
      this.error = '';
      try {
        const res = await axios.post('/select-role', { role });
        const redirect = res.data?.redirect || '/dashboard';
        window.location.href = redirect;
      } catch (e) {
        this.error = e.response?.data?.message || 'Error al seleccionar el perfil.';
      }
    }
  },
  mounted() {
    this.load();
  }
}
</script>

<style scoped>
.select-role-container .card {
  border-radius: 12px;
  box-shadow: 0 10px 40px rgba(0,0,0,0.08);
}
</style>
