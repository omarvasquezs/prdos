import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useAuthStore = defineStore('auth', () => {
  const user = ref(null);
  const isAuthenticated = computed(() => user.value !== null);

  const checkAuth = async () => {
    try {
      const response = await axios.get('/api/user');
      user.value = response.data;
    } catch (error) {
      user.value = null;
    }
  };

  const login = async (credentials) => {
    try {
      // Realizar login
      await axios.post('/login', credentials);
      
      // Obtener datos del usuario
      const response = await axios.get('/api/user');
      user.value = response.data;
      
      return { success: true };
    } catch (error) {
      return { 
        success: false, 
        message: error.response?.data?.message || 'Error al iniciar sesión' 
      };
    }
  };

  const logout = async () => {
    try {
      await axios.post('/logout');
      user.value = null;
    } catch (error) {
      console.error('Error al cerrar sesión:', error);
    }
  };

  return {
    user,
    isAuthenticated,
    checkAuth,
    login,
    logout
  };
});
