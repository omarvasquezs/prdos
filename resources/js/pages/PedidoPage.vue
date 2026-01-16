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
    <div v-else class="container-fluid px-3 py-2 flex-fill d-flex flex-column">
      <!-- Header Section -->
      <div class="header-section mb-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap">
          <div class="d-flex align-items-center">
            <button @click="volverAMesas" class="btn btn-outline-secondary btn-lg me-3">
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
            <!-- Badge de estado de entrega para Delivery/Recojo -->
            <span v-if="pedido?.tipo_atencion !== 'P' && pedido?.estado_entrega" class="badge fs-5 px-3 py-2 me-2"
              :class="estadoEntregaBadgeClass">
              <i :class="estadoEntregaIcon" class="me-1"></i>
              {{ pedido?.estado_entrega_texto }}
            </span>
            <!-- Badge de pagado -->
            <span v-if="pedido?.pagado" class="badge bg-info fs-5 px-3 py-2 me-2">
              <i class="fas fa-check-circle me-1"></i>
              Pagado - {{ pedido?.metodo_pago }}
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
            <div class="card-body p-0 d-flex flex-column items-scroll-container">
              <div v-if="pedido.items && pedido.items.length > 0" class="list-group list-group-flush flex-fill">
                <div v-for="item in pedido.items" :key="item.id" class="list-group-item py-3">
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
                        <button class="btn btn-outline-danger btn-sm" @click="eliminarItem(item.id)"
                          style="padding: 0.25rem 0.5rem;">
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
                    <p v-if="pedido.direccion_entrega" class="mb-1 small"><i class="fas fa-map-marker-alt me-2"></i>{{
                      pedido.direccion_entrega }}</p>
                    <p v-if="pedido.notas" class="mb-0 small text-muted"><i class="fas fa-sticky-note me-2"></i>{{
                      pedido.notas }}</p>
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
                <div v-if="pedido.tipo_atencion === 'D'"
                  class="d-flex justify-content-between mb-3 text-info align-items-center">
                  <span class="fs-5">Costo Delivery:</span>
                  <div v-if="isEditingDeliveryCost" class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-lg" style="width: 160px;">
                      <span class="input-group-text">S/</span>
                      <input type="number" class="form-control" v-model.number="newDeliveryCost" min="0" step="0.50">
                    </div>
                    <button class="btn btn-lg btn-success" @click="updateDeliveryCost" :disabled="isUpdatingCost">
                      <i class="fas fa-check"></i>
                    </button>
                    <button class="btn btn-lg btn-outline-secondary" @click="toggleEditDeliveryCost">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                  <div v-else class="d-flex align-items-center gap-3">
                    <span class="fw-bold fs-5">S/ {{ parseFloat(pedido.costo_delivery || 0).toFixed(2) }}</span>
                    <button class="btn btn-outline-secondary btn-lg" @click="toggleEditDeliveryCost"
                      v-if="!pedido.pagado">
                      <i class="fas fa-pencil-alt"></i>
                    </button>
                  </div>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-4">
                  <span class="fs-5 fw-bold">Total:</span>
                  <span class="fs-4 fw-bold text-success">S/ {{ parseFloat(pedido?.total || 0).toFixed(2) }}</span>
                </div>
              </div>

              <!-- Controls for Delivery/Recojo -->
              <div v-if="pedido.tipo_atencion !== 'P'" class="mb-3">
                <h6 class="mb-2"><i class="fas fa-tasks me-2"></i>Estado del Pedido</h6>
                <div class="d-grid gap-2">
                  <button class="btn btn-sm"
                    :class="pedido.estado_entrega === 'P' ? 'btn-warning' : 'btn-outline-warning'"
                    @click="cambiarEstado('P')" :disabled="pedido.estado_entrega === 'P'">
                    <i class="fas fa-clock me-2"></i>
                    En Preparación
                  </button>
                  <button class="btn btn-sm" :class="pedido.estado_entrega === 'L' ? 'btn-info' : 'btn-outline-info'"
                    @click="cambiarEstado('L')" :disabled="pedido.estado_entrega === 'L'">
                    <i class="fas fa-check me-2"></i>
                    Listo
                  </button>
                  <button class="btn btn-sm"
                    :class="pedido.estado_entrega === 'E' ? 'btn-success' : 'btn-outline-success'"
                    @click="cambiarEstado('E')" :disabled="pedido.estado_entrega === 'E'">
                    <i class="fas fa-check-double me-2"></i>
                    Entregado/Recogido
                  </button>
                </div>

                <!-- Button to mark as paid (if not paid yet) -->
                <button v-if="!pedido.pagado && pedido.total > 0" class="btn btn-outline-primary btn-sm w-100 mt-2"
                  @click="abrirModalMarcarPagado">
                  <i class="fas fa-money-bill-wave me-2"></i>
                  Marcar como Pagado
                </button>
              </div>

              <!-- Actions - Always at bottom -->
              <div class="d-grid gap-2 mt-auto">
                <button class="btn btn-success btn-lg" @click="abrirModalCobro"
                  :disabled="!pedido || !pedido.items || pedido.items.length === 0">
                  <i class="fas fa-money-bill me-2"></i>
                  {{ pedido?.tipo_atencion === 'P' ? 'Cobrar Pedido' : 'Generar Comprobante y Cerrar' }}
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
    <div v-if="mostrarModalCobro" class="modal fade show d-block" tabindex="-1"
      style="background-color: rgba(0,0,0,0.5);">
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
                  <input type="radio" class="btn-check" name="tipoComprobante" id="tipoN" value="N"
                    v-model="formCobro.tipo_comprobante">
                  <label class="btn btn-outline-primary" for="tipoN">Nota de Venta</label>

                  <input type="radio" class="btn-check" name="tipoComprobante" id="tipoB" value="B"
                    v-model="formCobro.tipo_comprobante">
                  <label class="btn btn-outline-primary" for="tipoB">Boleta</label>

                  <input type="radio" class="btn-check" name="tipoComprobante" id="tipoF" value="F"
                    v-model="formCobro.tipo_comprobante">
                  <label class="btn btn-outline-primary" for="tipoF">Factura</label>
                </div>
              </div>

              <!-- Campos para Factura -->
              <div v-if="formCobro.tipo_comprobante === 'F'" class="mb-3">
                <label for="ruc" class="form-label fw-bold">RUC *</label>
                <input type="text" id="ruc" v-model="formCobro.num_ruc" class="form-control" placeholder="11 dígitos"
                  maxlength="11" required>
              </div>
              <div v-if="formCobro.tipo_comprobante === 'F'" class="mb-3">
                <label for="razon_social" class="form-label fw-bold">Razón Social *</label>
                <input type="text" id="razon_social" v-model="formCobro.razon_social" class="form-control"
                  placeholder="Nombre de la empresa" required>
              </div>

              <!-- Campos para Boleta -->
              <div v-if="formCobro.tipo_comprobante === 'B'" class="mb-3">
                <label for="nombre_cliente" class="form-label fw-bold">Nombre Completo</label>
                <input type="text" id="nombre_cliente" v-model="formCobro.nombre_cliente" class="form-control"
                  placeholder="Nombres y apellidos del cliente">
              </div>
              <div v-if="formCobro.tipo_comprobante === 'B'" class="mb-3">
                <label for="dni_ce_cliente" class="form-label fw-bold">DNI / CE</label>
                <input type="text" id="dni_ce_cliente" v-model="formCobro.dni_ce_cliente" class="form-control"
                  placeholder="Documento de identidad" maxlength="9" pattern="[0-9]{8,9}" @input="validarDniCe">
                <small class="text-muted">Máximo 9 dígitos numéricos</small>
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

              <!-- Monto Pagado y Vuelto (Solo Efectivo) -->
              <div v-if="esEfectivo()" class="mb-3 p-3 bg-light rounded border">
                <div class="mb-3">
                  <label for="monto_pagado" class="form-label fw-bold">Monto Pagado</label>
                  <div class="input-group">
                    <span class="input-group-text">S/</span>
                    <input type="number" id="monto_pagado" v-model.number="formCobro.monto_pagado"
                      class="form-control form-control-lg" step="0.10" min="0" placeholder="0.00">
                  </div>
                </div>
                <div v-if="vuelto() !== null" class="d-flex justify-content-between align-items-center">
                  <span class="fw-bold">Vuelto:</span>
                  <span class="fs-4 fw-bold" :class="vuelto() >= 0 ? 'text-success' : 'text-danger'">
                    S/ {{ parseFloat(vuelto()).toFixed(2) }}
                  </span>
                </div>
                <div v-if="vuelto() < 0" class="text-danger small mt-1">
                  <i class="fas fa-exclamation-circle me-1"></i>
                  El monto es insuficiente
                </div>
              </div>

              <!-- Observaciones -->
              <div class="mb-3">
                <label for="observaciones" class="form-label">Observaciones</label>
                <textarea id="observaciones" v-model="formCobro.observaciones" class="form-control" rows="2"
                  placeholder="Notas adicionales (opcional)"></textarea>
              </div>

              <!-- Resumen -->
              <div class="alert alert-info">
                <template v-if="formCobro.tipo_comprobante === 'F'">
                  <strong>Total a cobrar:</strong>
                  S/ {{ parseFloat(pedido?.total || 0).toFixed(2) }} + IGV (10%) =
                  <strong class="fs-5">S/ {{ parseFloat(totalCobrar).toFixed(2) }}</strong>
                </template>
                <template v-else>
                  <strong>Total a cobrar:</strong> S/ {{ parseFloat(totalCobrar).toFixed(2) }}
                </template>
              </div>

              <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success btn-lg" :disabled="isSubmitting">
                  <span v-if="isSubmitting" class="spinner-border spinner-border-sm me-2" role="status"
                    aria-hidden="true"></span>
                  <i v-else class="fas fa-check me-2"></i>
                  {{ isSubmitting ? 'Procesando...' : 'Generar y Cobrar' }}
                </button>
                <button type="button" class="btn btn-outline-secondary" @click="cerrarModalCobro"
                  :disabled="isSubmitting">
                  Cancelar
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Productos -->
    <div v-if="mostrarModalProductos" class="modal fade show d-block" tabindex="-1"
      style="background-color: rgba(0,0,0,0.5);">
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
                <select id="categoriaSelect" class="form-select" v-model="categoriaSeleccionada">
                  <option value="">Todas las categorías</option>
                  <option v-for="categoria in categorias" :key="categoria.id" :value="categoria.id">
                    {{ categoria.nombre }}
                  </option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="cantidadInput" class="form-label">Cantidad:</label>
                <input id="cantidadInput" type="number" class="form-control" v-model.number="cantidadItem" min="1"
                  max="99">
              </div>
            </div>

            <!-- Lista de productos -->
            <div class="row">
              <div v-for="producto in filtrarProductos()" :key="producto.id" class="col-lg-4 col-md-6 mb-3">
                <div class="card h-100 producto-card"
                  :class="{ 'opacity-50 pe-none': producto.track_stock && producto.stock <= 0 }"
                  @click="confirmarAgregarItem(producto)">
                  <div class="card-body d-flex flex-column position-relative">
                    <span class="badge bg-secondary mb-2 align-self-start">{{ producto.categoria?.nombre }}</span>
                    <h6 class="card-title">{{ producto.nombre }}</h6>
                    <p class="card-text text-muted small flex-grow-1 mb-3">{{ producto.descripcion || 'Sin descripción'
                    }}
                    </p>

                    <div class="d-flex justify-content-between align-items-end mt-auto w-100">
                      <!-- Stock Indicator -->
                      <div v-if="producto.track_stock" class="stock-indicator">
                        <span class="badge" :class="producto.stock > 0 ? 'bg-info text-dark' : 'bg-danger'">
                          <i class="fas fa-box me-1"></i>
                          Stock: {{ producto.stock }}
                        </span>
                      </div>
                      <div v-else></div> <!-- Spacer -->

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

    <!-- Modal Marcar como Pagado -->
    <div v-if="mostrarModalMarcarPagado" class="modal fade show d-block" tabindex="-1"
      style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
              <i class="fas fa-money-bill-wave me-2"></i>
              Marcar como Pagado
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="cerrarModalMarcarPagado"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted mb-3">El pedido permanecerá abierto pero se registrará que ya fue pagado.</p>

            <!-- Método de Pago -->
            <div class="mb-3">
              <label class="form-label fw-bold">Método de Pago *</label>
              <select v-model="formMarcarPagado.metodo_pago_id" class="form-select form-select-lg" required>
                <option value="">Seleccionar método...</option>
                <option v-for="metodo in metodosPago" :key="metodo.id" :value="metodo.id">
                  {{ metodo.nom_metodo_pago }}
                </option>
              </select>
            </div>

            <!-- Monto Pagado y Vuelto (Solo Efectivo) -->
            <div v-if="esEfectivoMarcarPagado" class="mb-3 p-3 bg-light rounded border">
              <div class="mb-3">
                <label for="monto_pagado_mp" class="form-label fw-bold">Monto Pagado</label>
                <div class="input-group">
                  <span class="input-group-text">S/</span>
                  <input type="number" id="monto_pagado_mp" v-model.number="formMarcarPagado.monto_pagado"
                    class="form-control form-control-lg" step="0.10" min="0" placeholder="0.00">
                </div>
              </div>
              <div v-if="vueltoMarcarPagado !== null" class="d-flex justify-content-between align-items-center">
                <span class="fw-bold">Vuelto:</span>
                <span class="fs-4 fw-bold" :class="vueltoMarcarPagado >= 0 ? 'text-success' : 'text-danger'">
                  S/ {{ parseFloat(vueltoMarcarPagado).toFixed(2) }}
                </span>
              </div>
              <div v-if="vueltoMarcarPagado < 0" class="text-danger small mt-1">
                <i class="fas fa-exclamation-circle me-1"></i>
                El monto es insuficiente
              </div>
            </div>

            <!-- Resumen -->
            <div class="alert alert-info mb-0">
              <strong>Total pagado:</strong> S/ {{ parseFloat(pedido?.total || 0).toFixed(2) }}
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" @click="cerrarModalMarcarPagado" :disabled="isSubmitting">
              Cancelar
            </button>
            <button type="button" class="btn btn-primary" @click="marcarComoPagado"
              :disabled="isSubmitting || !formMarcarPagado.metodo_pago_id">
              <span v-if="isSubmitting">
                <span class="spinner-border spinner-border-sm me-2"></span>
                Procesando...
              </span>
              <span v-else>
                <i class="fas fa-check me-2"></i>
                Confirmar Pago
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Vista Previa PDF -->
    <div v-if="mostrarModalPDF" class="modal fade show d-block" tabindex="-1"
      style="background-color: rgba(0,0,0,0.7);">
      <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
              <i class="fas fa-file-pdf me-2"></i>
              Comprobante Generado
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="cerrarModalPDF"></button>
          </div>
          <div class="modal-body p-0" style="height: 80vh;">
            <iframe v-if="pdfURL" :src="pdfURL" style="width: 100%; height: 100%; border: none;"
              title="Vista previa del comprobante"></iframe>
          </div>
          <div class="modal-footer">
            <a v-if="pdfURL" :href="pdfURL" download="comprobante.pdf" class="btn btn-success">
              <i class="fas fa-download me-2"></i>
              Descargar PDF
            </a>
            <button type="button" class="btn btn-primary" @click="imprimirPDF">
              <i class="fas fa-print me-2"></i>
              Imprimir
            </button>
            <button type="button" class="btn btn-secondary" @click="cerrarModalPDF">
              <i class="fas fa-times me-2"></i>
              Cerrar y Volver
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
      mostrarModalMarcarPagado: false,
      mostrarModalPDF: false,
      pdfURL: null,
      metodosPago: [],
      isSubmitting: false,
      formCobro: {
        tipo_comprobante: 'N',
        metodo_pago_id: '',
        num_ruc: '',
        razon_social: '',
        nombre_cliente: '',
        dni_ce_cliente: '',
        observaciones: ''
      },
      formMarcarPagado: {
        metodo_pago_id: '',
        monto_pagado: null
      },
      isEditingDeliveryCost: false,
      newDeliveryCost: 0,
      isUpdatingCost: false
    }
  },

  computed: {
    vueltoMarcarPagado() {
      if (!this.formMarcarPagado.monto_pagado || !this.pedido) return null
      const monto = parseFloat(this.formMarcarPagado.monto_pagado)
      const total = parseFloat(this.pedido.total)
      if (isNaN(monto) || isNaN(total)) return null
      return monto - total
    },

    esEfectivoMarcarPagado() {
      if (!this.formMarcarPagado.metodo_pago_id) return false
      const metodo = this.metodosPago.find(m => m.id === this.formMarcarPagado.metodo_pago_id)
      return metodo && metodo.nom_metodo_pago.toLowerCase().includes('efectivo')
    },

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
    },

    estadoEntregaBadgeClass() {
      if (!this.pedido?.estado_entrega) return 'bg-secondary'

      if (this.pedido.estado_entrega === 'P') return 'bg-warning'
      if (this.pedido.estado_entrega === 'L') return 'bg-info'
      if (this.pedido.estado_entrega === 'E') return 'bg-success'
      return 'bg-secondary'
    },

    estadoEntregaIcon() {
      if (!this.pedido?.estado_entrega) return 'fas fa-question'

      if (this.pedido.estado_entrega === 'P') return 'fas fa-clock'
      if (this.pedido.estado_entrega === 'L') return 'fas fa-check'
      if (this.pedido.estado_entrega === 'E') return 'fas fa-check-double'
      return 'fas fa-question'
    },

    totalCobrar() {
      const base = parseFloat(this.pedido?.total || 0)
      if (this.formCobro.tipo_comprobante === 'F') {
        return base * 1.10
      }
      return base
    }
  },

  async mounted() {
    await this.cargarPedido()
    await this.cargarMetodosPago()
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

        // Pre-llenar datos del cliente si existen en el pedido
        if (this.pedido.cliente_nombre) {
          this.formCobro.nombre_cliente = this.pedido.cliente_nombre
        }

        // Pre-seleccionar método de pago si ya existe en el pedido
        if (this.pedido.metodo_pago_id) {
          this.formCobro.metodo_pago_id = this.pedido.metodo_pago_id
        }

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
        nombre_cliente: '',
        dni_ce_cliente: '',
        observaciones: '',
        monto_pagado: null
      }
    },



    vuelto() {
      if (!this.formCobro.monto_pagado || !this.pedido) return null
      const monto = parseFloat(this.formCobro.monto_pagado)
      const total = this.totalCobrar
      if (isNaN(monto) || isNaN(total)) return null
      return monto - total
    },

    esEfectivo() {
      if (!this.formCobro.metodo_pago_id) return false
      const metodo = this.metodosPago.find(m => m.id === this.formCobro.metodo_pago_id)
      return metodo && metodo.nom_metodo_pago.toLowerCase().includes('efectivo')
    },

    async cargarMetodosPago() {
      try {
        const response = await axios.get('/api/metodos-pago')
        this.metodosPago = response.data

        // Solo seleccionar el primero por defecto si no hay uno ya seleccionado del pedido
        if (this.metodosPago.length > 0 && !this.formCobro.metodo_pago_id) {
          this.formCobro.metodo_pago_id = this.metodosPago[0].id
        }
      } catch (error) {
        console.error('Error al cargar métodos de pago:', error)
        throw error
      }
    },

    validarDniCe(event) {
      // Solo permitir números y limitar a 9 caracteres
      const value = event.target.value.replace(/[^0-9]/g, '').slice(0, 9)
      this.formCobro.dni_ce_cliente = value
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

        // Crear blob y mostrar en modal
        const file = new Blob([response.data], { type: 'application/pdf' })
        const fileURL = URL.createObjectURL(file)

        // Guardar URL y mostrar modal
        this.pdfURL = fileURL
        this.mostrarModalCobro = false
        this.mostrarModalPDF = true

      } catch (error) {
        console.error('Error al generar comprobante:', error)
        if (error.response?.status === 422) {
          // Si la respuesta es un Blob (porque esperamos PDF), debemos leerla
          if (error.response.data instanceof Blob) {
            try {
              const text = await error.response.data.text();
              const data = JSON.parse(text);
              const errores = data.errors || {};
              const mensajeErrores = Object.values(errores).flat().join('\n') || data.message || 'Error de validación desconocido';
              alert('Errores en el formulario:\n' + mensajeErrores);
            } catch (e) {
              console.error('Error al parsear respuesta de error:', e);
              alert('Error de validación desconocido (no se pudo leer el error)');
            }
          } else {
            // Fallback por si no es Blob (aunque con responseType: blob debería serlo)
            const errores = error.response.data.errors || {};
            const mensajeErrores = Object.values(errores).flat().join('\n') || 'Error de validación desconocido';
            alert('Errores en el formulario:\n' + mensajeErrores);
          }
        } else {
          alert('Error al generar el comprobante. Intenta nuevamente.');
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

    async cambiarEstado(nuevoEstado) {
      if (!confirm(`¿Cambiar el estado a "${this.getEstadoTexto(nuevoEstado)}"?`)) {
        return
      }

      try {
        const response = await axios.post(`/api/pedidos-cola/${this.pedido.id}/estado-entrega`, {
          estado_entrega: nuevoEstado
        })

        this.pedido = response.data.pedido
        alert('Estado actualizado correctamente')
      } catch (error) {
        console.error('Error al cambiar estado:', error)
        alert('Error al cambiar el estado. Intenta nuevamente.')
      }
    },

    getEstadoTexto(estado) {
      const estados = {
        'P': 'En Preparación',
        'L': 'Listo',
        'E': 'Entregado/Recogido'
      }
      return estados[estado] || estado
    },

    abrirModalMarcarPagado() {
      this.formMarcarPagado.metodo_pago_id = ''
      this.mostrarModalMarcarPagado = true
    },

    cerrarModalMarcarPagado() {
      this.mostrarModalMarcarPagado = false
    },

    async marcarComoPagado() {
      if (!this.formMarcarPagado.metodo_pago_id) {
        alert('Por favor selecciona un método de pago')
        return
      }

      this.isSubmitting = true
      try {
        const response = await axios.post(`/api/pedidos-cola/${this.pedido.id}/marcar-pagado`, this.formMarcarPagado)

        this.pedido = response.data.pedido
        this.cerrarModalMarcarPagado()
        alert('Pedido marcado como pagado')
      } catch (error) {
        console.error('Error al marcar como pagado:', error)
        alert('Error al procesar el pago. Intenta nuevamente.')
      } finally {
        this.isSubmitting = false
      }
    },

    toggleEditDeliveryCost() {
      if (this.isEditingDeliveryCost) {
        this.isEditingDeliveryCost = false
      } else {
        this.newDeliveryCost = parseFloat(this.pedido.costo_delivery || 0)
        this.isEditingDeliveryCost = true
      }
    },

    async updateDeliveryCost() {
      try {
        this.isUpdatingCost = true
        const response = await axios.post(`/api/pedidos-cola/${this.pedido.id}/costo-delivery`, {
          costo_delivery: this.newDeliveryCost
        })

        this.pedido = response.data.pedido
        this.isEditingDeliveryCost = false
      } catch (error) {
        console.error('Error updating delivery cost:', error)
        alert('Error al actualizar el costo de delivery')
      } finally {
        this.isUpdatingCost = false
      }
    },

    cerrarModalPDF() {
      // Liberar el objeto URL para evitar fugas de memoria
      if (this.pdfURL) {
        URL.revokeObjectURL(this.pdfURL)
        this.pdfURL = null
      }
      this.mostrarModalPDF = false

      // Redirigir a la página de caja
      this.$router.push('/caja')
    },

    imprimirPDF() {
      if (this.pdfURL) {
        // Abrir en nueva ventana solo para imprimir
        const ventanaImpresion = window.open(this.pdfURL, '_blank')
        if (ventanaImpresion) {
          ventanaImpresion.onload = function () {
            ventanaImpresion.print()
          }
        }
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

      // Validar stock
      if (producto.track_stock && cantidad > producto.stock) {
        alert(`No es posible agregar ${cantidad} items. El stock disponible es: ${producto.stock}`)
        return
      }

      try {
        const response = await axios.post(`/api/pedidos/${this.pedido.id}/items`, {
          producto_id: producto.id,
          cantidad: cantidad
        })

        // Si llegamos aquí, la respuesta fue exitosa (código 200)

        // Actualizar stock localmente
        if (producto.track_stock) {
          const productInList = this.productos.find(p => p.id === producto.id)
          if (productInList) {
            productInList.stock -= cantidad
          }
        }

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

      // Obtener datos del item antes de eliminar para restaurar stock visualmente
      const itemToDelete = this.pedido.items.find(i => i.id === itemId)

      try {
        const response = await axios.delete(`/api/pedidos/${this.pedido.id}/items/${itemId}`)

        // Si llegamos aquí, la respuesta fue exitosa (código 200)

        // Restaurar stock localmente
        if (itemToDelete && itemToDelete.producto_id) {
          const productInList = this.productos.find(p => p.id === itemToDelete.producto_id)
          if (productInList && productInList.track_stock) {
            productInList.stock += parseInt(itemToDelete.cantidad)
          }
        }

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
  },

  beforeUnmount() {
    // Liberar el objeto URL si existe
    if (this.pdfURL) {
      URL.revokeObjectURL(this.pdfURL)
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
  overflow: hidden;
}

.flex-fill {
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 0;
}

.flex-fill .card-body {
  flex: 1;
  min-height: 0;
}

/* Items scroll container */
.items-scroll-container {
  max-height: calc(100vh - 250px);
  overflow-y: auto;
  overflow-x: hidden;
}

.items-scroll-container .list-group {
  flex: unset !important;
}

/* Resumen stays fixed */
.col-lg-4 .card {
  position: sticky;
  top: 1rem;
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
  border-radius: 12px;
  padding: 1rem;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.page-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #212529;
  margin: 0;
}

.page-subtitle {
  font-size: 0.9rem;
  color: #6c757d;
  font-weight: 400;
}

/* === CARDS === */
.card {
  border: none;
  border-radius: 12px;
  overflow: hidden;
}

.card-header {
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
  padding: 1rem 1.25rem;
}

.card-body {
  padding: 1.25rem;
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
/* Optimized for 1024x768 (EPOS terminal) */
@media (min-width: 1000px) and (max-width: 1100px) {
  .page-title {
    font-size: 1.4rem;
  }

  .page-subtitle {
    font-size: 0.85rem;
  }

  .items-scroll-container {
    max-height: calc(100vh - 220px);
  }

  .btn-lg {
    padding: 0.75rem 1.5rem;
  }
}

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