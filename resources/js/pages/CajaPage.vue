<template>
  <div class="caja-page">
    <!-- Loading Screen -->
    <div v-if="isLoading" class="fullscreen-loading">
      <div class="loading-content">
        <div class="spinner-border text-warning" role="status" style="width: 4rem; height: 4rem;">
          <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="loading-text mt-3">Cargando mesas...</p>
      </div>
    </div>

    <template v-else>
      <div v-if="isLoggingOut" class="fullscreen-loading">
        <div class="loading-content">
          <div class="spinner-border text-danger" role="status" style="width: 3.5rem; height: 3.5rem;">
            <span class="visually-hidden">Cerrando sesión...</span>
          </div>
          <p class="loading-text mt-3">Cerrando sesión...</p>
        </div>
      </div>

      <!-- Main Content -->
      <div class="container-fluid px-4 py-3">
      <!-- Header Section -->
      <div class="header-section mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
          <div>
            <h1 class="page-title mb-1">
              <i class="fas fa-utensils text-warning me-2"></i>
              Selección de Mesa
            </h1>
            <p class="page-subtitle mb-0 text-muted">Toca una mesa disponible para iniciar un nuevo pedido</p>
          </div>
          <div class="header-actions d-flex align-items-center">
            <button
              @click="logout"
              class="btn btn-outline-danger btn-lg me-3"
              :disabled="isLoggingOut"
            >
              <i class="fas" :class="isLoggingOut ? 'fa-spinner fa-spin' : 'fa-sign-out-alt'"></i>
              <span class="ms-2 d-none d-md-inline">Cerrar sesión</span>
            </button>
            <button 
              @click="refreshMesas" 
              class="btn btn-outline-secondary btn-lg me-3"
              :disabled="isRefreshing"
            >
              <i class="fas fa-sync-alt" :class="{ 'fa-spin': isRefreshing }"></i>
              <span class="ms-2 d-none d-md-inline">Actualizar</span>
            </button>
            <div class="stats-badge">
              <span class="badge bg-success fs-6 px-3 py-2 me-2">
                <i class="fas fa-check-circle me-1"></i>
                {{ mesasDisponibles }} Disponibles
              </span>
              <span class="badge bg-danger fs-6 px-3 py-2">
                <i class="fas fa-users me-1"></i>
                {{ mesasOcupadas }} Ocupadas
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Alert Messages -->
      <div v-if="alertMessage" class="alert alert-dismissible fade show" :class="alertClass" role="alert">
        <i :class="alertIcon" class="me-2"></i>
        {{ alertMessage }}
        <button type="button" class="btn-close" @click="clearAlert"></button>
      </div>

      <!-- Mesas Grid -->
      <div class="mesas-grid">
        <div 
          v-for="mesa in mesas" 
          :key="mesa.id"
          class="mesa-card"
          :class="mesaCardClass(mesa)"
          @click="seleccionarMesa(mesa)"
        >
          <!-- Mesa Icon -->
          <div class="mesa-icon">
            <i class="fas fa-utensils"></i>
          </div>

          <!-- Mesa Info -->
          <div class="mesa-info">
            <h3 class="mesa-numero">Mesa {{ mesa.num_mesa }}</h3>
            
            <!-- Estado Badge -->
            <div class="mesa-estado">
              <span class="badge" :class="estadoBadgeClass(mesa.estado)">
                <i :class="estadoIcon(mesa.estado)" class="me-1"></i>
                {{ estadoTexto(mesa.estado) }}
              </span>
            </div>

            <!-- Pedido Activo Info -->
            <div v-if="mesa.pedido_activo" class="pedido-info">
              <div class="comensales">
                <i class="fas fa-users text-muted me-1"></i>
                {{ mesa.pedido_activo.comensales }} {{ mesa.pedido_activo.comensales === 1 ? 'comensal' : 'comensales' }}
              </div>
              <div class="tiempo">
                <i class="fas fa-clock text-muted me-1"></i>
                {{ formatearTiempo(mesa.pedido_activo.fecha_apertura) }}
              </div>
              <div class="total" v-if="mesa.pedido_activo.total > 0">
                <i class="fas fa-money-bill text-success me-1"></i>
                S/ {{ mesa.pedido_activo.total.toFixed(2) }}
              </div>
            </div>
          </div>

          <!-- Action Button -->
          <div class="mesa-action">
            <button 
              class="btn btn-sm"
              :class="actionButtonClass(mesa)"
              v-if="mesa.estado === 'D'"
            >
              <i class="fas fa-plus me-1"></i>
              Ocupar
            </button>
            <button 
              class="btn btn-sm btn-outline-warning"
              v-else-if="mesa.estado === 'O'"
              @click.stop="verPedido(mesa)"
            >
              <i class="fas fa-eye me-1"></i>
              Ver Pedido
            </button>
          </div>
        </div>
      </div>

      <!-- Empty State -->
      <div v-if="mesas.length === 0 && !isLoading" class="empty-state text-center py-5">
        <i class="fas fa-table display-1 text-muted mb-3"></i>
        <h3 class="text-muted">No hay mesas configuradas</h3>
        <p class="text-muted">Contacta al administrador para configurar las mesas del restaurante.</p>
      </div>
      </div>
    </template>

    <!-- Modal de Comensales -->
    <div 
      v-if="showComensalesModal" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(0,0,0,0.5);"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg">
          <div class="modal-header bg-warning">
            <h4 class="modal-title text-dark">
              <i class="fas fa-utensils me-2"></i>
              Mesa {{ mesaSeleccionada?.num_mesa }}
            </h4>
            <button 
              type="button" 
              class="btn-close" 
              @click="cerrarModalComensales"
            ></button>
          </div>
          
          <div class="modal-body p-4">
            <h5 class="mb-4 text-center">¿Cuántos comensales van a ocupar esta mesa?</h5>
            
            <!-- Selector de Comensales -->
            <div class="comensales-selector">
              <div class="row g-3">
                <div 
                  v-for="num in opcionesComensales" 
                  :key="num"
                  class="col-4 col-md-3"
                >
                  <button
                    type="button"
                    class="btn btn-outline-primary btn-lg w-100 comensal-btn"
                    :class="{ 'active': comensalesSeleccionados === num }"
                    @click="comensalesSeleccionados = num"
                  >
                    <div class="d-flex flex-column align-items-center">
                      <i class="fas fa-users mb-2" style="font-size: 1.5rem;"></i>
                      <span class="fw-bold">{{ num }}</span>
                      <small class="text-muted">{{ num === 1 ? 'persona' : 'personas' }}</small>
                    </div>
                  </button>
                </div>
              </div>
            </div>

            <!-- Input manual para más de 12 -->
            <div class="mt-4">
              <label class="form-label">O ingresa manualmente:</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text">
                  <i class="fas fa-users"></i>
                </span>
                <input 
                  type="number" 
                  class="form-control" 
                  v-model.number="comensalesSeleccionados"
                  min="1" 
                  max="50"
                  placeholder="Número de comensales"
                >
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-secondary btn-lg me-2" 
              @click="cerrarModalComensales"
            >
              <i class="fas fa-times me-2"></i>
              Cancelar
            </button>
            <button 
              type="button" 
              class="btn btn-warning btn-lg"
              :disabled="!comensalesSeleccionados || isOcupandoMesa"
              @click="confirmarOcuparMesa"
            >
              <span v-if="isOcupandoMesa">
                <span class="spinner-border spinner-border-sm me-2"></span>
                Ocupando...
              </span>
              <span v-else>
                <i class="fas fa-check me-2"></i>
                Ocupar Mesa
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios'

export default {
  name: 'CajaPage',
  data() {
    return {
      // Estados principales
      isLoading: true,
      isRefreshing: false,
      isOcupandoMesa: false,
  isLoggingOut: false,
      
      // Datos
      mesas: [],
      
      // Modal de comensales
      showComensalesModal: false,
      mesaSeleccionada: null,
      comensalesSeleccionados: 2,
      opcionesComensales: [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12],
      
      // Alertas
      alertMessage: '',
      alertType: 'success' // success, error, warning, info
    }
  },

  computed: {
    mesasDisponibles() {
      return this.mesas.filter(mesa => mesa.estado === 'D').length
    },

    mesasOcupadas() {
      return this.mesas.filter(mesa => mesa.estado === 'O').length
    },

    alertClass() {
      const classes = {
        success: 'alert-success',
        error: 'alert-danger',
        warning: 'alert-warning',
        info: 'alert-info'
      }
      return classes[this.alertType] || 'alert-info'
    },

    alertIcon() {
      const icons = {
        success: 'fas fa-check-circle',
        error: 'fas fa-exclamation-triangle',
        warning: 'fas fa-exclamation-circle',
        info: 'fas fa-info-circle'
      }
      return icons[this.alertType] || 'fas fa-info-circle'
    }
  },

  async mounted() {
    await this.cargarMesas()
  },

  methods: {
    async cargarMesas() {
      try {
        this.isLoading = true
        const response = await axios.get('/api/mesas')
        this.mesas = response.data
      } catch (error) {
        console.error('Error al cargar mesas:', error)
        this.showAlert('Error al cargar las mesas. Intenta nuevamente.', 'error')
      } finally {
        this.isLoading = false
      }
    },

    async refreshMesas() {
      try {
        this.isRefreshing = true
        await this.cargarMesas()
        this.showAlert('Mesas actualizadas correctamente', 'success')
      } catch (error) {
        this.showAlert('Error al actualizar las mesas', 'error')
      } finally {
        this.isRefreshing = false
      }
    },

    seleccionarMesa(mesa) {
      if (mesa.estado === 'D') {
        this.mesaSeleccionada = mesa
        this.comensalesSeleccionados = 2
        this.showComensalesModal = true
      } else if (mesa.estado === 'O') {
        this.verPedido(mesa)
      }
    },

    cerrarModalComensales() {
      this.showComensalesModal = false
      this.mesaSeleccionada = null
      this.comensalesSeleccionados = 2
    },

    async confirmarOcuparMesa() {
      if (!this.mesaSeleccionada || !this.comensalesSeleccionados) return

      try {
        this.isOcupandoMesa = true
        
        const response = await axios.post(`/api/mesas/${this.mesaSeleccionada.id}/ocupar`, {
          comensales: this.comensalesSeleccionados
        })

        // Actualizar la mesa en la lista
        const mesaIndex = this.mesas.findIndex(m => m.id === this.mesaSeleccionada.id)
        if (mesaIndex !== -1) {
          this.mesas[mesaIndex] = { 
            ...this.mesas[mesaIndex], 
            estado: 'O',
            pedido_activo: response.data.pedido
          }
        }

        this.showAlert(`Mesa ${this.mesaSeleccionada.num_mesa} ocupada exitosamente`, 'success')
        this.cerrarModalComensales()

        // Redirigir directamente a la página de pedido
        this.$router.push({
          name: 'pedido',
          params: { id: response.data.pedido.id }
        })

      } catch (error) {
        console.error('Error al ocupar mesa:', error)
        this.showAlert('Error al ocupar la mesa. Intenta nuevamente.', 'error')
      } finally {
        this.isOcupandoMesa = false
      }
    },

    verPedido(mesa) {
      // Aquí puedes navegar a la página del pedido
      this.$router.push(`/caja/pedido/${mesa.pedido_activo.id}`)
    },

    async logout() {
      if (this.isLoggingOut) return
      try {
        this.isLoggingOut = true
        await axios.post('/logout')
        window.location.href = '/login'
      } catch (error) {
        console.error('Error al cerrar sesión:', error)
        this.showAlert('No se pudo cerrar sesión. Intenta nuevamente.', 'error')
      } finally {
        this.isLoggingOut = false
      }
    },

    // Métodos de utilidad
    mesaCardClass(mesa) {
      return [
        'mesa-card',
        `mesa-${mesa.estado}`,
        {
          'mesa-disponible': mesa.estado === 'D',
          'mesa-ocupada': mesa.estado === 'O'
        }
      ]
    },

    estadoBadgeClass(estado) {
      return {
        'D': 'bg-success',
        'O': 'bg-danger'
      }[estado] || 'bg-secondary'
    },

    estadoIcon(estado) {
      return {
        'D': 'fas fa-check-circle',
        'O': 'fas fa-users'
      }[estado] || 'fas fa-question-circle'
    },

    estadoTexto(estado) {
      return {
        'D': 'Disponible',
        'O': 'Ocupada'
      }[estado] || 'Desconocido'
    },

    actionButtonClass(mesa) {
      return mesa.estado === 'D' ? 'btn-success' : 'btn-outline-secondary'
    },

    formatearTiempo(fechaApertura) {
      const ahora = new Date()
      const apertura = new Date(fechaApertura)
      const diffMs = ahora - apertura
      const diffMinutos = Math.floor(diffMs / 60000)
      
      if (diffMinutos < 60) {
        return `${diffMinutos} min`
      } else {
        const horas = Math.floor(diffMinutos / 60)
        const minutos = diffMinutos % 60
        return `${horas}h ${minutos}m`
      }
    },

    showAlert(message, type = 'info') {
      this.alertMessage = message
      this.alertType = type
      
      // Auto-hide después de 5 segundos
      setTimeout(() => {
        this.clearAlert()
      }, 5000)
    },

    clearAlert() {
      this.alertMessage = ''
      this.alertType = 'info'
    }
  }
}
</script>

<style scoped>
/* === ESTILOS PRINCIPALES === */
.caja-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}

/* === LOADING SCREEN === */
.fullscreen-loading {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(8px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
}

.loading-content {
  text-align: center;
}

.loading-text {
  font-size: 1.2rem;
  color: #6c757d;
  font-weight: 500;
}

/* === HEADER === */
.header-section {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.page-title {
  font-size: 2rem;
  font-weight: 700;
  color: #212529;
  margin: 0;
}

.page-subtitle {
  font-size: 1.1rem;
  color: #6c757d;
  font-weight: 400;
}

.header-actions {
  gap: 1rem;
}

.stats-badge {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
}

/* === MESAS GRID === */
.mesas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.5rem;
  padding: 1rem 0;
}

/* === MESA CARD === */
.mesa-card {
  background: white;
  border-radius: 20px;
  padding: 1.5rem;
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
  border: 2px solid transparent;
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  cursor: pointer;
  position: relative;
  overflow: hidden;
  min-height: 200px;
  display: flex;
  flex-direction: column;
  user-select: none;
  -webkit-tap-highlight-color: transparent;
}

.mesa-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
}

.mesa-card:active {
  transform: translateY(-2px);
  transition: transform 0.1s ease;
}

/* Estados de mesa */
.mesa-card.mesa-D {
  border-color: #28a745;
  background: linear-gradient(135deg, #fff 0%, #f8fff9 100%);
}

.mesa-card.mesa-D:hover {
  border-color: #20c997;
  box-shadow: 0 12px 24px rgba(40, 167, 69, 0.2);
}

.mesa-card.mesa-O {
  border-color: #dc3545;
  background: linear-gradient(135deg, #fff 0%, #fff8f8 100%);
}

.mesa-card.mesa-O:hover {
  border-color: #c82333;
  box-shadow: 0 12px 24px rgba(220, 53, 69, 0.2);
}

/* Mesa Icon */
.mesa-icon {
  text-align: center;
  margin-bottom: 1rem;
}

.mesa-icon i {
  font-size: 3rem;
  color: #ffc107;
  filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
}

/* Mesa Info */
.mesa-info {
  flex: 1;
  text-align: center;
}

.mesa-numero {
  font-size: 1.5rem;
  font-weight: 700;
  color: #212529;
  margin-bottom: 0.75rem;
}

.mesa-estado {
  margin-bottom: 1rem;
}

.mesa-estado .badge {
  font-size: 0.9rem;
  font-weight: 600;
  padding: 0.5rem 1rem;
  border-radius: 50px;
}

/* Pedido Info */
.pedido-info {
  background: rgba(0, 0, 0, 0.03);
  border-radius: 12px;
  padding: 1rem;
  margin: 1rem 0;
  font-size: 0.9rem;
}

.pedido-info > div {
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pedido-info > div:last-child {
  margin-bottom: 0;
}

.total {
  font-weight: 700;
  color: #28a745 !important;
}

/* Mesa Action */
.mesa-action {
  margin-top: auto;
  text-align: center;
}

.mesa-action .btn {
  font-weight: 600;
  border-radius: 12px;
  padding: 0.75rem 1.5rem;
  font-size: 0.95rem;
  min-width: 120px;
}

/* === MODAL STYLES === */
.modal-content {
  border: none;
  border-radius: 20px;
  overflow: hidden;
}

.modal-header {
  border-bottom: none;
  padding: 1.5rem;
}

.modal-body {
  padding: 2rem;
}

.modal-footer {
  border-top: 1px solid rgba(0, 0, 0, 0.1);
  padding: 1.5rem;
}

/* Comensales Selector */
.comensales-selector {
  margin: 1.5rem 0;
}

.comensal-btn {
  border-radius: 16px;
  padding: 1.5rem 1rem;
  border: 2px solid #e9ecef;
  background: white;
  transition: all 0.3s ease;
  height: auto;
  min-height: 100px;
}

.comensal-btn:hover {
  border-color: #0d6efd;
  background-color: rgba(13, 110, 253, 0.05);
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(13, 110, 253, 0.15);
  color: #0d6efd;
}

.comensal-btn:hover i {
  color: #0d6efd;
}

.comensal-btn:hover .text-muted {
  color: #6c757d !important;
}

.comensal-btn.active {
  background: #0d6efd;
  border-color: #0d6efd;
  color: white;
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(13, 110, 253, 0.3);
}

.comensal-btn.active i {
  color: white;
}

.comensal-btn.active .text-muted {
  color: rgba(255, 255, 255, 0.8) !important;
}

/* === EMPTY STATE === */
.empty-state i {
  opacity: 0.3;
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
  .page-title {
    font-size: 1.75rem;
  }
  
  .header-actions {
    width: 100%;
    justify-content: space-between;
    margin-top: 1rem;
  }
  
  .mesas-grid {
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1rem;
  }
  
  .mesa-card {
    min-height: 180px;
    padding: 1.25rem;
  }
  
  .mesa-icon i {
    font-size: 2.5rem;
  }
  
  .mesa-numero {
    font-size: 1.3rem;
  }
  
  .stats-badge {
    justify-content: center;
  }
}

@media (max-width: 576px) {
  .mesas-grid {
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  }
  
  .comensal-btn {
    min-height: 80px;
    padding: 1rem 0.5rem;
  }
  
  .modal-dialog {
    margin: 0.5rem;
  }
}

/* === ANIMACIONES === */
@keyframes pulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.02); }
  100% { transform: scale(1); }
}

.mesa-disponible {
  animation: pulse 2s ease-in-out infinite;
}

/* === TOUCH OPTIMIZATIONS === */
@media (hover: none) and (pointer: coarse) {
  .mesa-card:hover {
    transform: none;
    box-shadow: 0 8px 16px rgba(0, 0, 0, 0.08);
  }
  
  .comensal-btn:hover {
    border-color: #0d6efd;
    background-color: rgba(13, 110, 253, 0.05);
    color: #0d6efd;
    transform: none;
    box-shadow: 0 2px 8px rgba(13, 110, 253, 0.1);
  }
  
  .comensal-btn:hover i {
    color: #0d6efd;
  }
  
  .mesa-disponible {
    animation: none;
  }
}

/* === ACCESSIBILITY === */
@media (prefers-reduced-motion: reduce) {
  .mesa-card,
  .comensal-btn,
  * {
    transition: none !important;
    animation: none !important;
  }
}

/* === FOCUS STYLES === */
.mesa-card:focus,
.comensal-btn:focus {
  outline: 3px solid rgba(13, 110, 253, 0.5);
  outline-offset: 2px;
}
</style>