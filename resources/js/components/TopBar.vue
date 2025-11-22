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
        <span class="user-name">{{ userUsername }}</span>
        <i class="fas fa-chevron-down ms-2"></i>

        <!-- Dropdown Menu -->
        <div v-if="showUserMenu" class="user-dropdown">
          <div class="dropdown-header">
            <div class="user-info">
              <strong>{{ userName }}</strong>
            </div>
          </div>
          <div class="dropdown-divider"></div>

          <a href="#" class="dropdown-item" @click.prevent="abrirModalCambiarPassword">
            <i class="fas fa-key me-2"></i>
            Cambiar contraseña
          </a>
          <a v-if="hasMultipleRoles" href="#" class="dropdown-item" @click.prevent="goSelectRole">
            <i class="fas fa-tags me-2"></i>
            Seleccionar rol
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

  <!-- Modal Cambiar Contraseña -->
  <div v-if="modalCambiarPassword" class="modal-overlay" @click.self="cerrarModalPassword">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-key me-2"></i>
          Cambiar Contraseña
        </h5>
        <button type="button" class="btn-close" @click="cerrarModalPassword">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nueva Contraseña</label>
          <input type="password" v-model="nuevaPassword" class="form-control" placeholder="Ingrese nueva contraseña" />
        </div>
        <div class="mb-3">
          <label class="form-label">Confirmar Contraseña</label>
          <input type="password" v-model="confirmarPassword" class="form-control"
            placeholder="Confirme la nueva contraseña" />
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" @click="cerrarModalPassword">
          <i class="fas fa-times me-2"></i>
          Cancelar
        </button>
        <button type="button" class="btn btn-primary" @click="cambiarPassword">
          <i class="fas fa-save me-2"></i>
          Guardar Cambios
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '@/stores/auth';
import { useRouter } from 'vue-router';
import axios from 'axios';

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
      showUserMenu: false,
      modalCambiarPassword: false,
      nuevaPassword: '',
      confirmarPassword: ''
    };
  },
  computed: {
    userName() {
      return this.authStore.user?.name || 'Usuario';
    },
    userUsername() {
      return this.authStore.user?.username || 'Usuario';
    },

    hasMultipleRoles() {
      return Array.isArray(this.authStore.user?.roles) && this.authStore.user.roles.length > 1;
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

    goSelectRole() {
      this.showUserMenu = false;
      this.router.push('/select-role');
    },
    abrirModalCambiarPassword() {
      this.showUserMenu = false;
      this.modalCambiarPassword = true;
      this.nuevaPassword = '';
      this.confirmarPassword = '';
    },
    cerrarModalPassword() {
      this.modalCambiarPassword = false;
      this.nuevaPassword = '';
      this.confirmarPassword = '';
    },
    async cambiarPassword() {
      if (!this.nuevaPassword || !this.confirmarPassword) {
        alert('Por favor complete todos los campos');
        return;
      }

      if (this.nuevaPassword !== this.confirmarPassword) {
        alert('Las contraseñas no coinciden');
        return;
      }

      if (this.nuevaPassword.length < 6) {
        alert('La contraseña debe tener al menos 6 caracteres');
        return;
      }

      try {
        const userId = this.authStore.user.id;
        await axios.post(`/api/admin/usuarios/${userId}/change-password`, {
          action: 'new',
          password: this.nuevaPassword,
          password_confirmation: this.confirmarPassword
        });

        alert('Contraseña cambiada exitosamente');
        this.cerrarModalPassword();
      } catch (error) {
        console.error('Error al cambiar contraseña:', error);
        alert('Error al cambiar la contraseña: ' + (error.response?.data?.message || 'Error desconocido'));
      }
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

/* Modal styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.modal-content {
  background: white;
  border-radius: 12px;
  width: 90%;
  max-width: 500px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.modal-header {
  padding: 1.5rem;
  border-bottom: 1px solid #e9ecef;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.modal-title {
  margin: 0;
  font-size: 1.25rem;
  font-weight: 600;
  color: #212529;
}

.btn-close {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #6c757d;
  cursor: pointer;
  padding: 0;
  width: 30px;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 4px;
  transition: all 0.2s;
}

.btn-close:hover {
  background: #f8f9fa;
  color: #212529;
}

.modal-body {
  padding: 1.5rem;
}

.modal-footer {
  padding: 1rem 1.5rem;
  border-top: 1px solid #e9ecef;
  display: flex;
  gap: 0.75rem;
  justify-content: flex-end;
}

.form-label {
  display: block;
  margin-bottom: 0.5rem;
  font-weight: 500;
  color: #495057;
  font-size: 0.9rem;
}

.form-control {
  display: block;
  width: 100%;
  padding: 0.75rem;
  font-size: 1rem;
  border: 1px solid #ced4da;
  border-radius: 6px;
  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.form-control:focus {
  border-color: #667eea;
  outline: 0;
  box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.btn {
  padding: 0.5rem 1rem;
  font-size: 0.95rem;
  border-radius: 6px;
  border: none;
  cursor: pointer;
  font-weight: 500;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-secondary {
  background: #6c757d;
  color: white;
}

.btn-secondary:hover {
  background: #5a6268;
}

.btn-primary {
  background: #667eea;
  color: white;
}

.btn-primary:hover {
  background: #5568d3;
}

.mb-3 {
  margin-bottom: 1rem;
}

.me-2 {
  margin-right: 0.5rem;
}
</style>
