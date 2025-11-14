<template>
  <div class="pedido-page">
    <!-- Loading Screen -->
    <div v-if="isLoading" class="fullscreen-loading">
      <div class="loading-content">
        <div class="spinner-border text-warning" role="status" style="width: 4rem; height: 4rem;">
          <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="loading-text mt-3">Cargando pedido...</p>
      </div>
    </div>

    <!-- Main Content -->
    <div v-else class="container-fluid px-4 py-3 flex-fill d-flex flex-column">
      <!-- Header Section -->
      <div class="header-section mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
          <div class="d-flex align-items-center">
            <button 
              @click="volverAMesas" 
              class="btn btn-outline-secondary btn-lg me-3"
            >
              <i class="fas fa-arrow-left me-2"></i>
              Volver
            </button>
            <div>
              <div class="d-flex align-items-center mb-1">
                <h1 class="page-title mb-0 me-3">
                  <i :class="pedidoIcon" class="text-warning me-2"></i>
                  Pedido {{ pedidoTitulo }}
                </h1>
                <span class="badge px-3 py-2" :class="pedidoBadgeClass">
                  <i :class="pedidoBadgeIcon" class="me-1"></i>
                  {{ pedidoBadgeText }}
                </span>
              </div>
              <p class="page-subtitle mb-0 text-muted">
                <template v-if="pedido?.tipo_atencion === 'P'">
                  {{ pedido?.comensales }} {{ pedido?.comensales === 1 ? 'comensal' : 'comensales' }} • 
                </template>
                <template v-else>
                  {{ pedido?.cliente_nombre }} • {{ pedido?.cliente_telefono }} • 
                </template>
                Iniciado {{ formatearTiempo(pedido?.fecha_apertura) }}
              </p>
            </div>
          </div>
          <div class="header-actions">
            <span class="badge bg-success fs-5 px-3 py-2 me-2">
              <i class="fas fa-clock me-1"></i>
              Abierto
            </span>
            <span class="badge bg-primary fs-5 px-3 py-2">
              <i class="fas fa-money-bill me-1"></i>
              S/ {{ parseFloat(pedido?.total || 0).toFixed(2) }}
            </span>
          </div>
        </div>
      </div>

      <!-- Error State -->
      <div v-if="error" class="alert alert-danger">
        <i class="fas fa-exclamation-triangle me-2"></i>
        {{ error }}
      </div>

      <!-- Pedido Content -->
      <div v-else-if="pedido" class="row g-4 flex-fill-content">
        <!-- Items del Pedido -->
        <div class="col-lg-8 d-flex">
          <div class="card shadow-sm flex-fill">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>
                Items del Pedido
              </h5>
              <button class="btn btn-outline-primary btn-sm" @click="agregarItem">
                <i class="fas fa-plus me-2"></i>
                Agregar Item
              </button>
            </div>
            <div class="card-body p-0 d-flex flex-column">
              <div v-if="pedido.items && pedido.items.length > 0" class="list-group list-group-flush flex-fill">
                <div 
                  v-for="item in pedido.items" 
                  :key="item.id"
                  class="list-group-item py-3"
                >
                  <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                      <h6 class="mb-1">{{ item.producto }}</h6>
                      <p class="mb-0 text-muted small">
                        S/ {{ parseFloat(item.precio_unitario).toFixed(2) }} × {{ item.cantidad }}
                      </p>
                    </div>
                    <div class="text-end">
                      <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-primary fs-6">
                          {{ item.cantidad }}
                        </span>
                        <button 
                          class="btn btn-outline-danger btn-sm"
                          @click="eliminarItem(item.id)"
                          style="padding: 0.25rem 0.5rem;"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                      <div class="fw-bold">
                        S/ {{ parseFloat(item.subtotal).toFixed(2) }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-else class="p-4 text-center text-muted d-flex flex-column justify-content-center flex-fill">
                <h5>No hay items en este pedido</h5>
                <p>El pedido está vacío. Agrega productos para comenzar.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Resumen -->
        <div class="col-lg-4 d-flex">
          <div class="card shadow-sm flex-fill">
            <div class="card-header bg-warning">
              <h5 class="card-title mb-0 text-dark">
                <i class="fas fa-calculator me-2"></i>
                Resumen del Pedido
              </h5>
            </div>
            <div class="card-body d-flex flex-column">
              <div class="flex-grow-1">
                <!-- Info for Presencial -->
                <template v-if="pedido.tipo_atencion === 'P'">
                  <div class="d-flex justify-content-between mb-2">
                    <span>Mesa:</span>
                    <span class="fw-bold">{{ pedido.mesa?.num_mesa }}</span>
                  </div>
                  <div class="d-flex justify-content-between mb-2">
                    <span>Comensales:</span>
                    <span class="fw-bold">{{ pedido.comensales }}</span>
                  </div>
                </template>

                <!-- Info for Delivery/Recojo -->
                <template v-else>
                  <div class="mb-3 p-3 bg-light rounded">
                    <h6 class="mb-2"><i class="fas fa-user me-2"></i>{{ pedido.cliente_nombre }}</h6>
                    <p class="mb-1 small"><i class="fas fa-phone me-2"></i>{{ pedido.cliente_telefono }}</p>
                    <p v-if="pedido.direccion_entrega" class="mb-1 small"><i class="fas fa-map-marker-alt me-2"></i>{{ pedido.direccion_entrega }}</p>
                    <p v-if="pedido.notas" class="mb-0 small text-muted"><i class="fas fa-sticky-note me-2"></i>{{ pedido.notas }}</p>
                  </div>
                </template>

                <div class="d-flex justify-content-between mb-2">
                  <span>Hora inicio:</span>
                  <span class="fw-bold">{{ formatearHora(pedido.fecha_apertura) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                  <span>Tiempo:</span>
                  <span class="fw-bold">{{ formatearTiempo(pedido.fecha_apertura) }}</span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                  <span>Items:</span>
                  <span class="fw-bold">{{ totalItems }}</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-4">
                  <span class="fs-5 fw-bold">Total:</span>
                  <span class="fs-4 fw-bold text-success">S/ {{ parseFloat(pedido?.total || 0).toFixed(2) }}</span>
                </div>
              </div>
              
              <!-- Actions - Always at bottom -->
              <div class="d-grid gap-2 mt-auto">
                <button class="btn btn-success btn-lg" @click="abrirModalCobro" :disabled="!pedido || !pedido.items || pedido.items.length === 0">
                  <i class="fas fa-money-bill me-2"></i>
                  Cobrar Pedido
                </button>
                <button class="btn btn-outline-danger btn-lg" @click="cancelarPedido" :disabled="!pedido">
                  <i class="fas fa-times me-2"></i>
                  Cancelar Pedido
                </button>
              </div>
            </div>
          </div>

        </div>
      </div>

      <!-- Not Found State -->
      <div v-else class="text-center py-5">
        <i class="fas fa-search display-1 text-muted mb-3" style="opacity: 0.3;"></i>
        <h3 class="text-muted">Pedido no encontrado</h3>
        <p class="text-muted">El pedido solicitado no existe o ha sido eliminado.</p>
        <button @click="volverAMesas" class="btn btn-primary">
          <i class="fas fa-arrow-left me-2"></i>
          Volver a Mesas
        </button>
      </div>
    </div>

    <!-- Modal de Cobro -->
    <div v-if="mostrarModalCobro" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-receipt me-2"></i>
              Generar Comprobante
            </h5>
            <button type="button" class="btn-close" @click="cerrarModalCobro"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="generarComprobante">
              <!-- Tipo de Comprobante -->
              <div class="mb-3">
                <label class="form-label fw-bold">Tipo de Comprobante</label>
                <div class="btn-group d-flex" role="group">
                  <input type="radio" class="btn-check" name="tipoComprobante" id="tipoN" value="N" v-model="formCobro.tipo_comprobante">
                  <label class="btn btn-outline-primary" for="tipoN">Nota de Venta</label>
                  
                  <input type="radio" class="btn-check" name="tipoComprobante" id="tipoB" value="B" v-model="formCobro.tipo_comprobante">
                  <label class="btn btn-outline-primary" for="tipoB">Boleta</label>
                  
                  <input type="radio" class="btn-check" name="tipoComprobante" id="tipoF" value="F" v-model="formCobro.tipo_comprobante">
                  <label class="btn btn-outline-primary" for="tipoF">Factura</label>
                </div>
              </div>

              <!-- Campos para Factura -->
              <div v-if="formCobro.tipo_comprobante === 'F'" class="mb-3">
                <label for="ruc" class="form-label fw-bold">RUC *</label>
                <input type="text" id="ruc" v-model="formCobro.num_ruc" class="form-control" placeholder="11 dígitos" maxlength="11" required>
              </div>
              <div v-if="formCobro.tipo_comprobante === 'F'" class="mb-3">
                <label for="razon_social" class="form-label fw-bold">Razón Social *</label>
                <input type="text" id="razon_social" v-model="formCobro.razon_social" class="form-control" placeholder="Nombre de la empresa" required>
              </div>

              <!-- Método de Pago -->
              <div class="mb-3">
                <label class="form-label fw-bold">Método de Pago *</label>
                <select v-model="formCobro.metodo_pago_id" class="form-select" required>
                  <option value="">Seleccionar método de pago...</option>
                  <option v-for="metodo in metodosPago" :key="metodo.id" :value="metodo.id">
                    {{ metodo.nom_metodo_pago }}
                  </option>
                </select>
              </div>

              <!-- Observaciones -->
              <div class="mb-3">
                <label for="observaciones" class="form-label">Observaciones</label>
                <textarea id="observaciones" v-model="formCobro.observaciones" class="form-control" rows="2" placeholder="Notas adicionales (opcional)"></textarea>
              </div>

              <!-- Resumen -->
              <div class="alert alert-info">
                <strong>Total a cobrar:</strong> S/ {{ parseFloat(pedido?.total || 0).toFixed(2) }}
              </div>

              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success btn-lg" :disabled="isSubmitting">
                  <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                  <i v-else class="fas fa-check me-2"></i>
                  {{ isSubmitting ? 'Procesando...' : 'Generar y Cobrar' }}
                </button>
                <button type="button" class="btn btn-outline-secondary" @click="cerrarModalCobro" :disabled="isSubmitting">
                  Cancelar
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Productos -->
    <div v-if="mostrarModalProductos" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-plus-circle me-2"></i>
              Agregar Producto
            </h5>
            <button type="button" class="btn-close" @click="cerrarModalProductos"></button>
          </div>
          <div class="modal-body">
            <!-- Filtro por categoría -->
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="categoriaSelect" class="form-label">Filtrar por categoría:</label>
                <select 
                  id="categoriaSelect" 
                  class="form-select" 
                  v-model="categoriaSeleccionada"
                >
                  <option value="">Todas las categorías</option>
                  <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">
                    {{ categoria.nombre }}
                  </option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="cantidadInput" class="form-label">Cantidad:</label>
                <input 
                  id="cantidadInput"
                  type="number" 
                  class="form-control" 
                  v-model.number="cantidadItem"
                  min="1"
                  max="99"
                >
              </div>
            </div>

            <!-- Lista de productos -->
            <div class="row">
              <div 
                v-for="producto in filtrarProductos()" 
                :key="producto.id" 
                class="col-lg-4 col-md-6 mb-3"
              >
                <div class="card h-100 producto-card" @click="confirmarAgregarItem(producto)">
                  <div class="card-body d-flex flex-column">
                    <span class="badge bg-secondary mb-2 align-self-start">{{ producto.categoria?.nombre }}</span>
                    <h6 class="card-title">{{ producto.nombre }}</h6>
                    <p class="card-text text-muted small flex-grow-1 mb-3">{{ producto.descripcion || 'Sin descripción' }}</p>
                    <div class="d-flex justify-content-end align-items-center mt-auto">
                      <strong class="text-success">S/ {{ parseFloat(producto.precio).toFixed(2) }}</strong>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div v-if="filtrarProductos().length === 0" class="text-center text-muted py-4">
              <i class="fas fa-search display-4 mb-3" style="opacity: 0.3;"></i>
              <h5>No hay productos disponibles</h5>
              <p>No se encontraron productos en esta categoría.</p>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="cerrarModalProductos">
              Cancelar
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
  name: 'PedidoPage',
  data() {
    return {
      pedido: null,
      isLoading: true,
      error: null,
      productos: [],
      categorias: [],
      mostrarModalProductos: false,
      cantidadItem: 1,
      categoriaSeleccionada: '',
      mostrarModalCobro: false,
      metodosPago: [],
      isSubmitting: false,
      formCobro: {
        tipo_comprobante: 'N',
        metodo_pago_id: '',
        num_ruc: '',
        razon_social: '',
        observaciones: ''
      }
    }
  },

  computed: {
    totalItems() {
      if (!this.pedido?.items) return 0
      return this.pedido.items.reduce((total, item) => total + parseInt(item.cantidad || 0), 0)
    },

    pedidoTitulo() {
      if (!this.pedido) return ''
      
      if (this.pedido.tipo_atencion === 'P') {
        return `- Mesa ${this.pedido.mesa?.num_mesa || ''}`
      } else if (this.pedido.tipo_atencion === 'D') {
        return `#${this.pedido.id} - Delivery`
      } else if (this.pedido.tipo_atencion === 'R') {
        return `#${this.pedido.id} - Recojo`
      }
      return `#${this.pedido.id}`
    },

    pedidoIcon() {
      if (!this.pedido) return 'fas fa-receipt'
      
      if (this.pedido.tipo_atencion === 'D') return 'fas fa-motorcycle'
      if (this.pedido.tipo_atencion === 'R') return 'fas fa-shopping-bag'
      return 'fas fa-receipt'
    },

    pedidoBadgeClass() {
      if (!this.pedido) return 'bg-secondary'
      
      if (this.pedido.tipo_atencion === 'P') return 'bg-danger'
      if (this.pedido.tipo_atencion === 'D') return 'bg-info'
      return 'bg-warning'
    },

    pedidoBadgeIcon() {
      if (!this.pedido) return 'fas fa-question'
      
      if (this.pedido.tipo_atencion === 'P') return 'fas fa-users'
      if (this.pedido.tipo_atencion === 'D') return 'fas fa-motorcycle'
      return 'fas fa-shopping-bag'
    },

    pedidoBadgeText() {
      if (!this.pedido) return ''
      
      if (this.pedido.tipo_atencion === 'P') return 'Ocupada'
      if (this.pedido.tipo_atencion === 'D') return 'Delivery'
      return 'Recojo'
    }
  },

  async mounted() {
    await this.cargarPedido()
  },

  methods: {
    async cargarPedido() {
      try {
        this.isLoading = true
        const pedidoId = this.$route.params.id
        
        // Obtener el pedido directamente
        const response = await axios.get(`/api/pedidos/${pedidoId}`)
        this.pedido = response.data
        
      } catch (error) {
        console.error('Error al cargar pedido:', error)
        if (error.response?.status === 404) {
          this.error = 'Pedido no encontrado'
        } else {
          this.error = 'Error al cargar el pedido. Intenta nuevamente.'
        }
      } finally {
        this.isLoading = false
      }
    },

    volverAMesas() {
      this.$router.push('/caja')
    },

    async abrirModalCobro() {
      try {
        await this.cargarMetodosPago()
        this.mostrarModalCobro = true
      } catch (error) {
        console.error('Error al abrir modal de cobro:', error)
        alert('Error al abrir el formulario de cobro')
      }
    },

    cerrarModalCobro() {
      this.mostrarModalCobro = false
      this.formCobro = {
        tipo_comprobante: 'N',
        metodo_pago_id: '',
        num_ruc: '',
        razon_social: '',
        observaciones: ''
      }
    },

    async cargarMetodosPago() {
      try {
        const response = await axios.get('/api/metodos-pago')
        this.metodosPago = response.data
        if (this.metodosPago.length > 0) {
          this.formCobro.metodo_pago_id = this.metodosPago[0].id
        }
      } catch (error) {
        console.error('Error al cargar métodos de pago:', error)
        throw error
      }
    },

    async generarComprobante() {
      if (!this.formCobro.metodo_pago_id) {
        alert('Por favor selecciona un método de pago')
        return
      }

      this.isSubmitting = true
      try {
        const response = await axios.post(`/api/pedidos/${this.pedido.id}/comprobante`, this.formCobro, {
          responseType: 'blob'
        })

        // Crear blob y abrir en nueva pestaña
        const file = new Blob([response.data], { type: 'application/pdf' })
        const fileURL = URL.createObjectURL(file)
        window.open(fileURL, '_blank')

        alert('Comprobante generado exitosamente')
        this.$router.push('/caja')
      } catch (error) {
        console.error('Error al generar comprobante:', error)
        if (error.response?.status === 422) {
          // Errores de validación
          const errores = error.response.data.errors
          const mensajeErrores = Object.values(errores).flat().join('\n')
          alert('Errores en el formulario:\n' + mensajeErrores)
        } else {
          alert('Error al generar el comprobante. Intenta nuevamente.')
        }
      } finally {
        this.isSubmitting = false
      }
    },

    async cobrarPedido() {

      try {
        this.isLoading = true
        const response = await axios.post(`/api/pedidos/${this.pedido.id}/cobrar`)
        
        // Si llegamos aquí, la respuesta fue exitosa
        alert('Pedido cobrado exitosamente')
        this.$router.push('/caja')
        
      } catch (error) {
        console.error('Error al cobrar pedido:', error)
        alert('Error al procesar el cobro. Intenta nuevamente.')
      } finally {
        this.isLoading = false
      }
    },

    async cancelarPedido() {
      if (!confirm('¿Estás seguro de cancelar este pedido? Esta acción no se puede deshacer.')) {
        return
      }

      try {
        this.isLoading = true
        const response = await axios.post(`/api/pedidos/${this.pedido.id}/cancelar`)
        
        // Si llegamos aquí, la respuesta fue exitosa
        alert('Pedido cancelado exitosamente')
        this.$router.push('/caja')
        
      } catch (error) {
        console.error('Error al cancelar pedido:', error)
        alert('Error al cancelar el pedido. Intenta nuevamente.')
            } finally {
        this.isLoading = false
      }
    },

    formatearTiempo(fechaApertura) {
      const ahora = new Date()
      const apertura = new Date(fechaApertura)
      const diffMs = ahora - apertura
      const diffMinutos = Math.floor(diffMs / 60000)
      
      if (diffMinutos < 60) {
        return `hace ${diffMinutos} min`
      } else {
        const horas = Math.floor(diffMinutos / 60)
        const minutos = diffMinutos % 60
        return `hace ${horas}h ${minutos}m`
      }
    },

    formatearHora(fecha) {
      return new Date(fecha).toLocaleTimeString('es-ES', {
        hour: '2-digit',
        minute: '2-digit'
      })
    },

    async agregarItem() {
      try {
        this.mostrarModalProductos = true
        // Cargar productos si no están cargados
        if (this.productos.length === 0) {
          await this.cargarProductos()
        }
      } catch (error) {
        console.error('Error al abrir modal de productos:', error)
        alert('Error al cargar los productos')
      }
    },

    async cargarProductos() {
      try {
        const response = await axios.get('/api/productos')
        this.productos = response.data

        // También cargar categorías
        const categoriasResponse = await axios.get('/api/productos/categorias')
        this.categorias = categoriasResponse.data
      } catch (error) {
        console.error('Error al cargar productos:', error)
        throw error
      }
    },

    async confirmarAgregarItem(producto) {
      const cantidad = parseInt(this.cantidadItem) || 1
      
      try {
        const response = await axios.post(`/api/pedidos/${this.pedido.id}/items`, {
          producto_id: producto.id,
          cantidad: cantidad
        })

        // Si llegamos aquí, la respuesta fue exitosa (código 200)
        // Recargar el pedido para mostrar los cambios
        await this.cargarPedido()
        this.cerrarModalProductos()
        
        // Mostrar mensaje de éxito opcional
        // alert(`${producto.nombre} agregado al pedido`)

      } catch (error) {
        console.error('Error al agregar item:', error)
        alert('Error al agregar el producto al pedido')
      }
    },

    async eliminarItem(itemId) {
      if (!confirm('¿Eliminar este producto del pedido?')) {
        return
      }

      try {
        const response = await axios.delete(`/api/pedidos/${this.pedido.id}/items/${itemId}`)

        // Si llegamos aquí, la respuesta fue exitosa (código 200)
        // Recargar el pedido para mostrar los cambios
        await this.cargarPedido()
        
      } catch (error) {
        console.error('Error al eliminar item:', error)
        alert('Error al eliminar el producto del pedido')
      }
    },

    cerrarModalProductos() {
      this.mostrarModalProductos = false
      this.cantidadItem = 1
      this.categoriaSeleccionada = ''
    },

    filtrarProductos() {
      if (!this.categoriaSeleccionada) return this.productos
      return this.productos.filter(p => p.categoria.id == this.categoriaSeleccionada)
    }
  }
}
</script>

<style scoped>
/* === ESTILOS PRINCIPALES === */
.pedido-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  display: flex;
  flex-direction: column;
}

/* === FLEX FILL CONTENT === */
.flex-fill-content {
  flex: 1;
  min-height: 0;
}

.flex-fill {
  flex: 1;
  display: flex;
  flex-direction: column;
}

.flex-fill .card-body {
  flex: 1;
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

/* === CARDS === */
.card {
  border: none;
  border-radius: 16px;
  overflow: hidden;
}

.card-header {
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  padding: 1.25rem 1.5rem;
}

.card-body {
  padding: 1.5rem;
}

/* === LIST ITEMS === */
.list-group-item {
  border-left: none;
  border-right: none;
  border-top: none;
}

.list-group-item:last-child {
  border-bottom: none;
}

/* === BUTTONS === */
.btn {
  border-radius: 12px;
  font-weight: 600;
  padding: 0.75rem 1.5rem;
}

.btn-lg {
  padding: 1rem 2rem;
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
  .page-title {
    font-size: 1.75rem;
  }
  
  .header-actions {
    width: 100%;
    justify-content: center;
    margin-top: 1rem;
  }
  
  .header-actions .badge {
    margin: 0 0.25rem;
  }
}

@media (max-width: 576px) {
  .page-title {
    font-size: 1.5rem;
  }
  
  .d-flex.align-items-center.mb-1 {
    flex-direction: column;
    align-items: flex-start !important;
    gap: 0.5rem;
  }
  
  .container-fluid {
    padding-left: 1rem;
    padding-right: 1rem;
  }
  
  .card-body {
    padding: 1rem;
  }
  
  /* Productos en móvil - 2 columnas */
  .modal-body .row .col-lg-4 {
    flex: 0 0 50%;
    max-width: 50%;
  }
}

@media (min-width: 768px) and (max-width: 991px) {
  /* Tablets - 2 columnas */
  .modal-body .row .col-lg-4 {
    flex: 0 0 50%;
    max-width: 50%;
  }
}

/* === MODAL PRODUCTOS === */
.modal.show {
  display: block !important;
}

.producto-card {
  cursor: pointer;
  transition: all 0.2s ease;
  border: 2px solid transparent;
}

.producto-card:hover {
  border-color: #007bff;
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
}

.producto-card .card-body {
  padding: 1rem;
}

.producto-card .card-title {
  font-size: 1rem;
  font-weight: 600;
  margin-bottom: 0.5rem;
}

.producto-card .card-text {
  font-size: 0.85rem;
  line-height: 1.4;
}
</style>