<template>
  <div class="select-role-page">
    <!-- Fullscreen Loading Overlay -->
    <div v-if="navigating" class="fullscreen-loading-overlay">
      <div class="loading-content">
        <div class="loading-spinner"></div>
        <h3 class="loading-text">Accediendo al sistema...</h3>
        <p class="loading-subtext">Por favor espera un momento</p>
      </div>
    </div>
    
    <div class="container">
      <div class="selection-card">
        <h1 class="title">Seleccionar Perfil</h1>
        <p class="subtitle">Elige el perfil con el que quieres acceder al sistema</p>

        <div v-if="loading" class="text-center my-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Cargando...</span>
          </div>
        </div>

        <div v-else class="roles-wrapper" :class="{ 'scroll-mode': roles.length > 2, 'centered-mode': roles.length <= 2 }">
          <button 
            v-show="roles.length > 2 && canScrollLeft" 
            class="scroll-arrow left-arrow"
            @click="scrollLeft"
            type="button"
          >
            <i class="fas fa-chevron-left"></i>
          </button>
          
          <div 
            class="roles-container" 
            :class="{ 'centered': roles.length <= 2, 'scrollable': roles.length > 2 }"
            ref="rolesContainer" 
            @scroll="checkScrollPosition"
          >
            <div 
              v-for="role in roles" 
              :key="role"
              class="role-card"
              :class="{ 'selected': selectedRole === role }"
              @click="selectRole(role)"
            >
              <div class="role-icon">
                <i v-if="role === 'administrador'" class="fas fa-user-tie"></i>
                <i v-else-if="role === 'caja'" class="fas fa-cash-register"></i>
                <i v-else class="fas fa-user"></i>
              </div>
              <div class="role-name">{{ getRoleDisplayName(role) }}</div>
            </div>
          </div>
          
          <button 
            v-show="roles.length > 2 && canScrollRight" 
            class="scroll-arrow right-arrow"
            @click="scrollRight"
            type="button"
          >
            <i class="fas fa-chevron-right"></i>
          </button>
        </div>

        <button 
          v-if="!loading && selectedRole"
          @click="continueWithRole"
          class="continue-btn"
          :disabled="!selectedRole || navigating"
          :class="{ 'loading': navigating }"
        >
          <span v-if="!navigating">Continuar</span>
          <span v-else class="button-loading">
            <i class="fas fa-spinner fa-spin"></i>
            Accediendo...
          </span>
        </button>



        <div v-if="error" class="error-message">
          <i class="fas fa-exclamation-triangle"></i>
          {{ error }}
        </div>
      </div>
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
      error: '',
      selectedRole: '',
      canScrollLeft: false,
      canScrollRight: false,
      navigating: false
    }
  },
  methods: {
    async load() {
      this.loading = true;
      try {
        const res = await axios.get('/api/user');
        this.roles = res.data?.roles || [];
        
        // If user has only one role, auto-select it
        if (this.roles.length === 1) {
          this.selectedRole = this.roles[0];
          await this.continueWithRole();
        } else {
          // Initialize scroll state after roles are loaded
          this.initializeScrollState();
        }
      } catch (e) {
        this.error = 'No se pudo obtener los perfiles. Intenta iniciar sesión nuevamente.';
      } finally {
        this.loading = false;
      }
    },
    
    getRoleDisplayName(role) {
      const roleNames = {
        'administrador': 'Administrador',
        'caja': 'Caja'
      };
      return roleNames[role] || role;
    },
    
    selectRole(role) {
      this.selectedRole = role;
      this.error = '';
    },
    
    async continueWithRole() {
      if (!this.selectedRole) return;
      
      this.error = '';
      this.navigating = true;
      
      try {
        const res = await axios.post('/select-role', { role: this.selectedRole });
        const redirect = res.data?.redirect || '/dashboard';
        
        // Keep the spinner visible during navigation
        window.location.href = redirect;
      } catch (e) {
        this.error = e.response?.data?.message || 'Error al seleccionar el perfil.';
        this.navigating = false;
      }
      // Note: No finally block - we want the spinner to stay visible until page navigation
    },
    
    checkScrollPosition() {
      const container = this.$refs.rolesContainer;
      if (!container || this.roles.length <= 2) {
        this.canScrollLeft = false;
        this.canScrollRight = false;
        return;
      }
      
      this.canScrollLeft = container.scrollLeft > 0;
      this.canScrollRight = container.scrollLeft < container.scrollWidth - container.clientWidth;
    },
    
    scrollLeft() {
      const container = this.$refs.rolesContainer;
      if (container) {
        container.scrollBy({ left: -200, behavior: 'smooth' });
      }
    },
    
    scrollRight() {
      const container = this.$refs.rolesContainer;
      if (container) {
        container.scrollBy({ left: 200, behavior: 'smooth' });
      }
    },
    
    initializeScrollState() {
      this.$nextTick(() => {
        this.checkScrollPosition();
      });
    }
  },
  mounted() {
    this.load();
    this.initializeScrollState();
    window.addEventListener('resize', this.checkScrollPosition);
  },
  
  beforeUnmount() {
    window.removeEventListener('resize', this.checkScrollPosition);
  }
}
</script>

<style scoped>
.select-role-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}

.container {
  max-width: 600px;
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.selection-card {
  background: white;
  border-radius: 20px;
  padding: 40px;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
}

.title {
  font-size: 2.5rem;
  font-weight: 700;
  color: #2c3e50;
  margin-bottom: 10px;
  line-height: 1.2;
}

.subtitle {
  font-size: 1.1rem;
  color: #7f8c8d;
  margin-bottom: 40px;
  font-weight: 400;
}

.roles-wrapper {
  position: relative;
  margin-bottom: 50px;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
}

.roles-wrapper.scroll-mode {
  gap: 15px;
  justify-content: flex-start;
}

.roles-wrapper.centered-mode {
  justify-content: center;
}

.roles-container {
  display: flex;
  scroll-behavior: smooth;
}

/* Centered layout for 2 or fewer roles */
.roles-container.centered {
  justify-content: center;
  align-items: center;
  flex-wrap: wrap;
  gap: 30px;
  padding: 20px 0;
  width: 100%;
  margin: 0 auto;
}

/* Scrollable layout for 3+ roles */
.roles-container.scrollable {
  overflow-x: auto;
  scrollbar-width: none; /* Firefox */
  -ms-overflow-style: none; /* IE and Edge */
  flex: 1;
  gap: 20px;
  padding: 10px 0;
}

.roles-container.scrollable::-webkit-scrollbar {
  display: none; /* Chrome, Safari and Opera */
}

.role-card {
  background: #f8f9fa;
  border: 2px solid #e9ecef;
  border-radius: 15px;
  padding: 25px 15px;
  cursor: pointer;
  transition: all 0.3s ease;
  text-align: center;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

/* For centered layout (2 or fewer roles) */
.roles-container.centered .role-card {
  min-width: 200px;
  width: 200px;
  padding: 35px 25px;
  flex: none;
}

/* For scrollable layout (3+ roles) */
.roles-container.scrollable .role-card {
  min-width: 140px;
  width: 140px;
  flex: none;
}

.role-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
  border-color: #667eea;
}

.role-card.selected {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border-color: #667eea;
  color: white;
}

.role-icon {
  color: #667eea;
  margin-bottom: 15px;
}

/* Icon sizes based on layout */
.roles-container.centered .role-icon {
  font-size: 3.5rem;
  margin-bottom: 20px;
}

.roles-container.scrollable .role-icon {
  font-size: 2.5rem;
  margin-bottom: 10px;
}

.role-card.selected .role-icon {
  color: white;
}

.role-name {
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  line-height: 1.2;
  word-break: break-word;
  hyphens: auto;
}

/* Different text sizes based on layout */
.roles-container.centered .role-name {
  font-size: 1.2rem;
  font-weight: 700;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.roles-container.scrollable .role-name {
  font-size: 0.85rem;
  letter-spacing: 0.3px;
}

.continue-btn {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  border: none;
  color: white;
  padding: 15px 40px;
  border-radius: 50px;
  font-size: 1.1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 30px;
}

.continue-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

.continue-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
  transform: none;
  box-shadow: none;
}

.continue-btn.loading {
  opacity: 0.8;
  cursor: wait;
}

.button-loading {
  display: flex;
  align-items: center;
  gap: 8px;
  justify-content: center;
}

.button-loading i {
  font-size: 1rem;
}

.scroll-arrow {
  background: #667eea;
  border: none;
  color: white;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.3s ease;
  font-size: 1rem;
  box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

.scroll-arrow:hover {
  background: #5a6fd8;
  transform: scale(1.1);
  box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4);
}

.scroll-arrow:active {
  transform: scale(0.95);
}

.left-arrow {
  order: -1;
}

.right-arrow {
  order: 1;
}

.error-message {
  background: #ffe6e6;
  color: #d63031;
  padding: 15px 20px;
  border-radius: 10px;
  border-left: 4px solid #d63031;
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 20px;
}

.error-message i {
  font-size: 1.2rem;
}

/* Responsive design */
@media (max-width: 768px) {
  .selection-card {
    padding: 30px 20px;
  }
  
  .title {
    font-size: 2rem;
  }
  
  .roles-wrapper.scroll-mode {
    gap: 10px;
  }
  
  .scroll-arrow {
    width: 35px;
    height: 35px;
    font-size: 0.9rem;
  }
  
  /* Mobile adjustments for centered layout */
  .roles-container.centered {
    gap: 20px;
    padding: 15px 0;
  }
  
  .roles-container.centered .role-card {
    min-width: 160px;
    width: 160px;
    padding: 25px 15px;
  }
  
  .roles-container.centered .role-icon {
    font-size: 2.8rem;
    margin-bottom: 15px;
  }
  
  .roles-container.centered .role-name {
    font-size: 1rem;
  }
  
  /* Mobile adjustments for scrollable layout */
  .roles-container.scrollable .role-card {
    min-width: 120px;
    width: 120px;
    padding: 20px 10px;
  }
  
  .roles-container.scrollable .role-icon {
    font-size: 2rem;
    margin-bottom: 8px;
  }
  
  .roles-container.scrollable .role-name {
    font-size: 0.75rem;
    letter-spacing: 0.2px;
  }
}

/* Loading spinner */
.spinner-border {
  width: 3rem;
  height: 3rem;
}

/* Fullscreen Loading Overlay */
.fullscreen-loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  width: 100vw;
  height: 100vh;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  opacity: 0;
  animation: fadeIn 0.3s ease-out forwards;
}

@keyframes fadeIn {
  to {
    opacity: 1;
  }
}

.loading-content {
  text-align: center;
  color: white;
}

.loading-spinner {
  width: 60px;
  height: 60px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid white;
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin: 0 auto 30px;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}

.loading-text {
  font-size: 1.8rem;
  font-weight: 700;
  margin-bottom: 10px;
  color: white;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.loading-subtext {
  font-size: 1.1rem;
  font-weight: 400;
  color: rgba(255, 255, 255, 0.9);
  margin: 0;
}
</style>
