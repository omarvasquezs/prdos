<template>
  <div class="top-bar">
    <div class="top-bar-left">
      <h4 class="mb-0">
        <i class="fas fa-utensils me-2"></i>
        POLLERIA PARD'S
      </h4>
    </div>
    <div class="top-bar-right">
      <div class="user-menu" @click="toggleUserMenu" ref="userMenuTrigger">
        <div class="user-avatar">
          <i class="fas fa-user"></i>
        </div>
        <span class="user-name">{{ userName }}</span>
        <i class="fas fa-chevron-down ms-2"></i>
        
        <!-- Dropdown Menu -->
        <div v-if="showUserMenu" class="user-dropdown">
          <div class="dropdown-header">
            <div class="user-info">
              <strong>{{ userName }}</strong>
              <small class="text-muted d-block">{{ userRole }}</small>
            </div>
          </div>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item" @click.prevent="verPerfil">
            <i class="fas fa-user-circle me-2"></i>
            Mi Perfil
          </a>
          <a href="#" class="dropdown-item" @click.prevent="configuracion">
            <i class="fas fa-cog me-2"></i>
            Configuración
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item text-danger" @click.prevent="cerrarSesion">
            <i class="fas fa-sign-out-alt me-2"></i>
            Cerrar Sesión
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';

export default {
  name: 'TopBar',
  setup() {
    const authStore = useAuthStore();
    const router = useRouter();
    
    return {
      authStore,
      router
    };
  },
  data() {
    return {
      showUserMenu: false
    };
  },
  computed: {
    userName() {
      return this.authStore.user?.name || 'Usuario';
    },
    userRole() {
      return this.authStore.activeRole || 'Sin rol';
    }
  },
  mounted() {
    document.addEventListener('click', this.handleClickOutside);
  },
  beforeUnmount() {
    document.removeEventListener('click', this.handleClickOutside);
  },
  methods: {
    toggleUserMenu() {
      this.showUserMenu = !this.showUserMenu;
    },
    handleClickOutside(event) {
      if (this.$refs.userMenuTrigger && !this.$refs.userMenuTrigger.contains(event.target)) {
        this.showUserMenu = false;
      }
    },
    verPerfil() {
      this.showUserMenu = false;
      alert('Ver perfil (por implementar)');
    },
    configuracion() {
      this.showUserMenu = false;
      alert('Configuración (por implementar)');
    },
    async cerrarSesion() {
      this.showUserMenu = false;
      await this.authStore.logout();
      window.location.href = '/login';
    }
  }
};
</script>

<style scoped>
.top-bar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  height: 60px;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 2rem;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  z-index: 1000;
}

.top-bar-left h4 {
  font-weight: 600;
  font-size: 1.3rem;
}

.top-bar-right {
  display: flex;
  align-items: center;
  gap: 1.5rem;
}

.user-menu {
  position: relative;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.2s;
}

.user-menu:hover {
  background: rgba(255, 255, 255, 0.1);
}

.user-avatar {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: white;
  color: #667eea;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
}

.user-name {
  font-weight: 500;
}

.user-dropdown {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  background: white;
  border-radius: 8px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
  min-width: 220px;
  overflow: hidden;
  z-index: 1001;
}

.dropdown-header {
  padding: 1rem;
  background: #f8f9fa;
}

.user-info strong {
  color: #212529;
  font-size: 0.95rem;
}

.user-info small {
  font-size: 0.8rem;
}

.dropdown-divider {
  height: 1px;
  background: #e9ecef;
  margin: 0;
}

.dropdown-item {
  display: flex;
  align-items: center;
  padding: 0.75rem 1rem;
  color: #495057;
  text-decoration: none;
  transition: background 0.2s;
}

.dropdown-item:hover {
  background: #f8f9fa;
}

.dropdown-item.text-danger {
  color: #dc3545;
}

.dropdown-item.text-danger:hover {
  background: #fff5f5;
}
</style>
