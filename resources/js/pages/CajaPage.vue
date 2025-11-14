<template>
  <div class="caja-page">
    <!-- Loading Screen -->
    <div v-if="isLoading" class="fullscreen-loading">
      <div class="loading-content">
        <div class="spinner-border text-warning" role="status" style="width: 4rem; height: 4rem;">
          <span class="visually-hidden">Cargando...</span>
        </div>
        <p class="loading-text mt-3">Preparando caja...</p>
      </div>
    </div>

    <template v-else>
      <div v-if="isLoggingOut" class="logout-loading-overlay">
        <div class="logout-loading-content">
          <div class="logout-loading-spinner"></div>
          <h3 class="logout-loading-text">Cerrando sesión...</h3>
          <p class="logout-loading-subtext">Por favor espera un momento</p>
        </div>
      </div>

      <!-- Main Content -->
      <div class="container-fluid px-4 py-3">
      <!-- Alert Messages -->
      <div v-if="alertMessage" class="alert alert-dismissible fade show" :class="alertClass" role="alert">
        <i :class="alertIcon" class="me-2"></i>
        {{ alertMessage }}
        <button type="button" class="btn-close" @click="clearAlert"></button>
      </div>

      <!-- Placeholder when caja is closed -->
      <div v-if="!cajaEstaAbierta" class="caja-closed-placeholder text-center py-5">
        <i class="fas fa-cash-register display-4 text-muted mb-3"></i>
        <h3 class="mb-3">La caja no está abierta</h3>
        <p class="text-muted mb-4">
          Abre la caja para gestionar pedidos y movimientos del día.
        </p>
        <button
          class="btn btn-success btn-lg"
          @click="abrirModalApertura"
          :disabled="isProcessingApertura"
        >
          <span v-if="isProcessingApertura">
            <span class="spinner-border spinner-border-sm me-2"></span>
            Abriendo...
          </span>
          <span v-else>
            <i class="fas fa-cash-register me-2"></i>
            Abrir Caja
          </span>
        </button>
      </div>

      <template v-else>
        <!-- Sidebar de Tipo de Atención -->
        <div class="atencion-sidebar">
          <button 
            class="atencion-sidebar-item" 
            :class="{ 'active': tipoAtencionActivo === 'P' }"
            @click="cambiarTipoAtencion('P')"
          >
            <i class="fas fa-utensils"></i>
            <span class="atencion-label">Presencial</span>
          </button>
          <button 
            class="atencion-sidebar-item" 
            :class="{ 'active': tipoAtencionActivo === 'D' }"
            @click="cambiarTipoAtencion('D')"
          >
            <i class="fas fa-motorcycle"></i>
            <span class="atencion-label">Delivery</span>
            <span class="atencion-badge" v-if="pedidosDelivery.length > 0">{{ pedidosDelivery.length }}</span>
          </button>
          <button 
            class="atencion-sidebar-item" 
            :class="{ 'active': tipoAtencionActivo === 'R' }"
            @click="cambiarTipoAtencion('R')"
          >
            <i class="fas fa-shopping-bag"></i>
            <span class="atencion-label">Recojo</span>
            <span class="atencion-badge" v-if="pedidosRecojo.length > 0">{{ pedidosRecojo.length }}</span>
          </button>
          <div class="sidebar-divider"></div>
          <button 
            class="atencion-sidebar-item sidebar-action sidebar-cerrar-caja"
            @click="abrirModalCierre"
            :disabled="isProcessingCierre"
          >
            <i class="fas" :class="isProcessingCierre ? 'fa-spinner fa-spin' : 'fa-lock'"></i>
            <span class="atencion-label">Cerrar Caja</span>
          </button>
          <button 
            class="atencion-sidebar-item sidebar-action"
            @click="abrirModalMovimientos"
            :disabled="isLoadingMovimientos"
          >
            <i class="fas" :class="isLoadingMovimientos ? 'fa-spinner fa-spin' : 'fa-list-alt'"></i>
            <span class="atencion-label">Movimientos</span>
          </button>
          <button 
            class="atencion-sidebar-item sidebar-action sidebar-logout"
            @click="logout"
            :disabled="isLoggingOut"
          >
            <i class="fas" :class="isLoggingOut ? 'fa-spinner fa-spin' : 'fa-sign-out-alt'"></i>
            <span class="atencion-label">Salir</span>
          </button>
        </div>

        <!-- Contenido Principal con Sidebar -->
        <div class="contenido-con-sidebar">
        
        <!-- Header Section (solo para Presencial) -->
        <div v-if="tipoAtencionActivo === 'P'" class="header-section mb-4">
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
                @click="refreshMesas" 
                class="btn btn-outline-secondary btn-lg"
                :disabled="isRefreshing || !cajaEstaAbierta"
              >
                <i class="fas fa-sync-alt" :class="{ 'fa-spin': isRefreshing }"></i>
                <span class="ms-2 d-none d-md-inline">Actualizar</span>
              </button>
            </div>
          </div>
        </div>
        
        <!-- Mesas Grid (solo si tipo presencial) -->
        <div v-if="tipoAtencionActivo === 'P'" class="mesas-grid">
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
        <div v-if="mesas.length === 0 && !isLoading && tipoAtencionActivo === 'P'" class="empty-state text-center py-5">
          <i class="fas fa-table display-1 text-muted mb-3"></i>
          <h3 class="text-muted">No hay mesas configuradas</h3>
          <p class="text-muted">Contacta al administrador para configurar las mesas del restaurante.</p>
        </div>

        <!-- Vista Delivery -->
        <div v-if="tipoAtencionActivo === 'D'" class="delivery-view">
          <div class="header-section mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
              <div>
                <h1 class="page-title mb-1">
                  <i class="fas fa-motorcycle text-warning me-2"></i>
                  Cola de Delivery
                </h1>
                <p class="page-subtitle mb-0 text-muted">Gestiona los pedidos a domicilio</p>
              </div>
              <div class="header-actions d-flex align-items-center">
                <button class="btn btn-primary btn-lg" @click="abrirModalNuevoPedido('D')">
                  <i class="fas fa-plus me-2"></i>
                  <span class="d-none d-md-inline">Nuevo Pedido</span>
                  <span class="d-md-none">Nuevo</span>
                </button>
              </div>
            </div>
          </div>
          
          <div v-if="isLoadingPedidos" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3">Cargando pedidos...</p>
          </div>

          <div v-else-if="pedidosDelivery.length === 0" class="text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No hay pedidos de delivery pendientes</h4>
          </div>

          <div v-else class="pedidos-grid">
            <div 
              v-for="pedido in pedidosDelivery" 
              :key="pedido.id"
              class="pedido-card"
              @click="verPedidoCola(pedido)"
            >
              <div class="pedido-header">
                <h5>#{{ pedido.id }} - {{ pedido.cliente_nombre }}</h5>
                <span class="badge bg-warning">{{ pedido.estado_texto }}</span>
              </div>
              <div class="pedido-body">
                <p><i class="fas fa-phone me-2"></i>{{ pedido.cliente_telefono }}</p>
                <p><i class="fas fa-map-marker-alt me-2"></i>{{ pedido.direccion_entrega }}</p>
                <p class="text-muted small"><i class="fas fa-clock me-2"></i>{{ formatearTiempo(pedido.fecha_apertura) }}</p>
              </div>
              <div class="pedido-footer">
                <strong>Total: {{ formatCurrency(pedido.total) }}</strong>
                <span class="badge bg-info">{{ pedido.items.length }} items</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Vista Recojo -->
        <div v-if="tipoAtencionActivo === 'R'" class="recojo-view">
          <div class="header-section mb-4">
            <div class="d-flex align-items-center justify-content-between flex-wrap">
              <div>
                <h1 class="page-title mb-1">
                  <i class="fas fa-shopping-bag text-warning me-2"></i>
                  Cola de Recojo
                </h1>
                <p class="page-subtitle mb-0 text-muted">Gestiona los pedidos para llevar</p>
              </div>
              <div class="header-actions d-flex align-items-center">
                <button class="btn btn-primary btn-lg" @click="abrirModalNuevoPedido('R')">
                  <i class="fas fa-plus me-2"></i>
                  <span class="d-none d-md-inline">Nuevo Pedido</span>
                  <span class="d-md-none">Nuevo</span>
                </button>
              </div>
            </div>
          </div>
          
          <div v-if="isLoadingPedidos" class="text-center py-5">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-3">Cargando pedidos...</p>
          </div>

          <div v-else-if="pedidosRecojo.length === 0" class="text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No hay pedidos de recojo pendientes</h4>
          </div>

          <div v-else class="pedidos-grid">
            <div 
              v-for="pedido in pedidosRecojo" 
              :key="pedido.id"
              class="pedido-card"
              @click="verPedidoCola(pedido)"
            >
              <div class="pedido-header">
                <h5>#{{ pedido.id }} - {{ pedido.cliente_nombre }}</h5>
                <span class="badge bg-warning">{{ pedido.estado_texto }}</span>
              </div>
              <div class="pedido-body">
                <p><i class="fas fa-phone me-2"></i>{{ pedido.cliente_telefono }}</p>
                <p v-if="pedido.notas" class="text-muted small"><i class="fas fa-sticky-note me-2"></i>{{ pedido.notas }}</p>
                <p class="text-muted small"><i class="fas fa-clock me-2"></i>{{ formatearTiempo(pedido.fecha_apertura) }}</p>
              </div>
              <div class="pedido-footer">
                <strong>Total: {{ formatCurrency(pedido.total) }}</strong>
                <span class="badge bg-info">{{ pedido.items.length }} items</span>
              </div>
            </div>
          </div>
        </div>
        </div>
      </template>
      </div>
    </template>

    <!-- Modal de Apertura de Caja -->
    <div 
      v-if="showAperturaModal" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(0,0,0,0.5);"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
          <div class="modal-header bg-success text-white">
            <h5 class="modal-title">
              <i class="fas fa-cash-register me-2"></i>
              Abrir caja
            </h5>
            <button 
              type="button" 
              class="btn-close" 
              @click="cerrarModalApertura"
              :disabled="isProcessingApertura"
            ></button>
          </div>

          <div class="modal-body">
            <p class="mb-3">
              Ingresa el monto inicial disponible en la caja para comenzar las operaciones del día.
            </p>
            <div class="mb-3">
              <label class="form-label">Monto de apertura</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text">S/</span>
                <input 
                  type="number" 
                  class="form-control" 
                  min="0" 
                  step="0.01"
                  v-model="openForm.monto"
                  :disabled="isProcessingApertura"
                  placeholder="0.00"
                >
              </div>
              <div v-if="openFormError" class="text-danger small mt-2">
                {{ openFormError }}
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-outline-secondary" 
              @click="cerrarModalApertura"
              :disabled="isProcessingApertura"
            >
              Cancelar
            </button>
            <button 
              type="button" 
              class="btn btn-success"
              @click="confirmarApertura"
              :disabled="isProcessingApertura || openForm.monto === ''"
            >
              <span v-if="isProcessingApertura">
                <span class="spinner-border spinner-border-sm me-2"></span>
                Guardando...
              </span>
              <span v-else>
                <i class="fas fa-check me-2"></i>
                Abrir Caja
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Cierre de Caja -->
    <div 
      v-if="showCierreModal" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(0,0,0,0.5);"
    >
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg">
          <div class="modal-header bg-danger text-white">
            <h5 class="modal-title">
              <i class="fas fa-lock me-2"></i>
              Cerrar caja
            </h5>
            <button 
              type="button" 
              class="btn-close" 
              @click="cerrarModalCierre"
              :disabled="isProcessingCierre"
            ></button>
          </div>

          <div class="modal-body">
            <p class="mb-3">
              Registra el monto final en caja al momento del cierre.
            </p>
            <div class="mb-3">
              <label class="form-label">Monto de cierre</label>
              <div class="input-group input-group-lg">
                <span class="input-group-text">S/</span>
                <input 
                  type="number" 
                  class="form-control" 
                  min="0" 
                  step="0.01"
                  v-model="closeForm.monto"
                  :disabled="isProcessingCierre"
                  placeholder="0.00"
                >
              </div>
              <div v-if="closeFormError" class="text-danger small mt-2">
                {{ closeFormError }}
              </div>
            </div>
          </div>

          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-outline-secondary" 
              @click="cerrarModalCierre"
              :disabled="isProcessingCierre"
            >
              Cancelar
            </button>
            <button 
              type="button" 
              class="btn btn-danger"
              @click="confirmarCierre"
              :disabled="isProcessingCierre || closeForm.monto === ''"
            >
              <span v-if="isProcessingCierre">
                <span class="spinner-border spinner-border-sm me-2"></span>
                Cerrando...
              </span>
              <span v-else>
                <i class="fas fa-check me-2"></i>
                Cerrar Caja
              </span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Movimientos -->
    <div 
      v-if="showMovimientosModal" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(0,0,0,0.5);"
    >
      <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
        <div class="modal-content shadow-lg">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
              <i class="fas fa-list-alt me-2"></i>
              Movimientos del día
            </h5>
            <button 
              type="button" 
              class="btn-close" 
              @click="cerrarModalMovimientos"
              :disabled="isLoadingMovimientos"
            ></button>
          </div>

          <div class="modal-body">
            <!-- Caja Status Info -->
            <div class="alert mb-4" :class="cajaEstaAbierta ? 'alert-success' : 'alert-warning'">
              <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                  <i :class="cajaEstaAbierta ? 'fas fa-cash-register' : 'fas fa-lock'" class="fs-2"></i>
                </div>
                <div class="flex-grow-1">
                  <h6 class="alert-heading mb-1">
                    {{ cajaEstaAbierta ? 'Caja abierta' : cajaStatus.hasRecordToday ? 'Caja cerrada' : 'Caja pendiente de apertura' }}
                  </h6>
                  <p class="mb-2 small">
                    <template v-if="cajaEstaAbierta && cajaRegistro">
                      La caja se abrió el {{ formatDateTime(cajaRegistro.datetime_apertura) }} por
                      {{ cajaRegistro.usuario_apertura || 'usuario desconocido' }} con un monto inicial de
                      {{ formatCurrency(cajaRegistro.monto_apertura) }}.
                    </template>
                    <template v-else-if="cajaStatus.hasRecordToday && cajaRegistro">
                      La caja está cerrada. La última apertura fue el {{ formatDateTime(cajaRegistro.datetime_apertura) }}.
                      <span v-if="cajaRegistro.datetime_cierre">
                        Se cerró el {{ formatDateTime(cajaRegistro.datetime_cierre) }}<span v-if="cajaRegistro.usuario_cierre"> por {{ cajaRegistro.usuario_cierre }}</span><span v-else-if="cajaRegistro.usuario_apertura"> por {{ cajaRegistro.usuario_apertura }}</span>.
                      </span>
                      <span v-else>
                        Aún no registra un cierre.
                      </span>
                    </template>
                    <template v-else>
                      Debes abrir la caja para registrar las operaciones del día actual.
                    </template>
                  </p>
                  <div class="d-flex flex-wrap gap-3 small" v-if="cajaRegistro">
                    <span>
                      <i class="fas fa-hourglass-start me-1"></i>
                      Apertura: {{ formatDateTime(cajaRegistro.datetime_apertura) }}
                    </span>
                    <span>
                      <i class="fas fa-coins me-1"></i>
                      Monto apertura: {{ formatCurrency(cajaRegistro.monto_apertura) }}
                    </span>
                    <span v-if="cajaRegistro.datetime_cierre">
                      <i class="fas fa-hourglass-end me-1"></i>
                      Cierre: {{ formatDateTime(cajaRegistro.datetime_cierre) }}
                    </span>
                    <span v-if="cajaRegistro.monto_cierre !== null">
                      <i class="fas fa-wallet me-1"></i>
                      Monto cierre: {{ formatCurrency(cajaRegistro.monto_cierre) }}
                    </span>
                  </div>
                </div>
              </div>
            </div>

            <!-- Filtro de fecha -->
            <div class="mb-4 date-filter-section">
              <div class="row align-items-end g-3">
                <div class="col-md-8">
                  <label for="fechaMovimientos" class="form-label fw-bold">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Fecha de consulta
                  </label>
                  <input 
                    type="date" 
                    id="fechaMovimientos"
                    class="form-control form-control-lg"
                    v-model="fechaMovimientos"
                    :disabled="isLoadingMovimientos"
                    :max="hoy"
                  >
                </div>
                <div class="col-md-4">
                  <button 
                    class="btn btn-primary btn-lg w-100"
                    @click="fetchMovimientos"
                    :disabled="isLoadingMovimientos"
                  >
                    <i class="fas" :class="isLoadingMovimientos ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                    <span class="ms-2">Consultar</span>
                  </button>
                </div>
              </div>
            </div>

            <div v-if="isLoadingMovimientos" class="text-center py-5">
              <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Cargando...</span>
              </div>
              <p class="mt-3 text-muted">Obteniendo movimientos...</p>
            </div>

            <div v-else>
              <div v-if="movimientosError" class="alert alert-danger" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                {{ movimientosError }}
              </div>

              <template v-else>
                <div class="row g-3 movimientos-summary mb-4">
                  <div class="col-md-4">
                    <div class="summary-card h-100">
                      <div class="summary-icon bg-success-subtle text-success">
                        <i class="fas fa-wallet"></i>
                      </div>
                      <div class="summary-label">Total cobrado</div>
                      <div class="summary-value">{{ formatCurrency(movimientosResumen.montoTotal) }}</div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="summary-card h-100">
                      <div class="summary-icon bg-info-subtle text-info">
                        <i class="fas fa-receipt"></i>
                      </div>
                      <div class="summary-label">Operaciones</div>
                      <div class="summary-value">{{ movimientosResumen.totalRegistros }}</div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <div class="summary-card h-100">
                      <div class="summary-icon bg-warning-subtle text-warning">
                        <i class="fas fa-layer-group"></i>
                      </div>
                      <div class="summary-label">Métodos de pago</div>
                      <div class="summary-value">{{ movimientosResumen.porMetodo.length }}</div>
                    </div>
                  </div>
                </div>

                <div class="metodo-breakdown mb-4" v-if="movimientosResumen.porMetodo.length">
                  <h6 class="text-uppercase text-muted fw-bold mb-3">Detalle por método de pago</h6>
                  <div class="row g-3">
                    <div class="col-md-6 col-lg-4" v-for="item in movimientosResumen.porMetodo" :key="`metodo-${item.metodo_pago_id}`">
                      <div class="metodo-card">
                        <div class="metodo-header d-flex justify-content-between align-items-center">
                          <span class="metodo-nombre">{{ item.metodo_pago }}</span>
                          <span class="badge bg-light text-dark">{{ item.cantidad }} ops</span>
                        </div>
                        <div class="metodo-monto">{{ formatCurrency(item.monto_total) }}</div>
                      </div>
                    </div>
                  </div>
                </div>

                <div v-if="movimientos.length" class="table-responsive movimientos-table">
                  <table class="table table-striped align-middle">
                    <thead>
                      <tr>
                        <th scope="col">Hora</th>
                        <th scope="col">Comprobante</th>
                        <th scope="col">Tipo</th>
                        <th scope="col">Método de pago</th>
                        <th scope="col" class="text-end">Monto</th>
                        <th scope="col">Registrado por</th>
                        <th scope="col" class="text-center">Acciones</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="mov in movimientos" :key="mov.id">
                        <td>{{ formatHour(mov.fecha) }}</td>
                        <td>{{ mov.cod_comprobante || '—' }}</td>
                        <td>{{ mov.tipo_comprobante_nombre || '—' }}</td>
                        <td>{{ mov.metodo_pago }}</td>
                        <td class="text-end">{{ formatCurrency(mov.monto) }}</td>
                        <td>{{ mov.usuario || '—' }}</td>
                        <td class="text-center">
                          <button 
                            class="btn btn-sm btn-primary" 
                            @click="verComprobante(mov.cod_comprobante)"
                            :disabled="!mov.cod_comprobante"
                          >
                            <i class="fas fa-file-alt me-1"></i>
                            Ver
                          </button>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>

                <div v-else class="text-center text-muted py-4">
                  <i class="fas fa-inbox fa-2x mb-3"></i>
                  <p class="mb-0">Aún no se registran movimientos en el día actual.</p>
                </div>
              </template>
            </div>
          </div>

          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-outline-secondary" 
              @click="cerrarModalMovimientos"
              :disabled="isLoadingMovimientos"
            >
              Cerrar
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Comprobante -->
    <div 
      v-if="showComprobanteModal" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(0,0,0,0.5);"
    >
      <div class="modal-dialog modal-dialog-centered modal-fullscreen">
        <div class="modal-content shadow-lg">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
              <i class="fas fa-file-invoice me-2"></i>
              Comprobante: {{ comprobanteSeleccionado }}
            </h5>
            <button 
              type="button" 
              class="btn-close btn-close-white" 
              @click="cerrarComprobante"
            ></button>
          </div>

          <div class="modal-body p-0">
            <iframe 
              v-if="comprobanteUrl"
              :src="comprobanteUrl" 
              style="width: 100%; height: calc(100vh - 120px); border: none;"
              frameborder="0"
            ></iframe>
          </div>

          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-secondary" 
              @click="cerrarComprobante"
            >
              <i class="fas fa-arrow-left me-2"></i>
              Regresar
            </button>
          </div>
        </div>
      </div>
    </div>

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

    <!-- Modal Nuevo Pedido Delivery/Recojo -->
    <div 
      v-if="showModalNuevoPedido" 
      class="modal fade show d-block" 
      tabindex="-1" 
      style="background-color: rgba(0,0,0,0.5);"
    >
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content shadow-lg">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
              <i :class="nuevoPedidoForm.tipo === 'D' ? 'fas fa-motorcycle' : 'fas fa-shopping-bag'" class="me-2"></i>
              Nuevo Pedido {{ nuevoPedidoForm.tipo === 'D' ? 'Delivery' : 'Recojo' }}
            </h5>
            <button 
              type="button" 
              class="btn-close btn-close-white" 
              @click="cerrarModalNuevoPedido"
            ></button>
          </div>
          
          <div class="modal-body p-4">
            <form @submit.prevent="crearNuevoPedido">
              <div class="mb-3">
                <label class="form-label fw-bold">Nombre del Cliente *</label>
                <input 
                  type="text" 
                  class="form-control form-control-lg"
                  v-model="nuevoPedidoForm.cliente_nombre"
                  placeholder="Ej: Juan Pérez"
                  required
                >
              </div>

              <div class="mb-3">
                <label class="form-label fw-bold">Teléfono *</label>
                <input 
                  type="tel" 
                  class="form-control form-control-lg"
                  v-model="nuevoPedidoForm.cliente_telefono"
                  placeholder="Ej: 987654321"
                  required
                >
              </div>

              <div class="mb-3" v-if="nuevoPedidoForm.tipo === 'D'">
                <label class="form-label fw-bold">Dirección de Entrega *</label>
                <textarea 
                  class="form-control"
                  v-model="nuevoPedidoForm.direccion_entrega"
                  placeholder="Ej: Av. Principal 123, Ref: Frente al parque"
                  rows="3"
                  required
                ></textarea>
              </div>

              <div class="mb-3">
                <label class="form-label fw-bold">Notas (Opcional)</label>
                <textarea 
                  class="form-control"
                  v-model="nuevoPedidoForm.notas"
                  placeholder="Instrucciones especiales, método de pago, etc."
                  rows="2"
                ></textarea>
              </div>

              <div v-if="nuevoPedidoError" class="alert alert-danger">
                {{ nuevoPedidoError }}
              </div>
            </form>
          </div>

          <div class="modal-footer">
            <button 
              type="button" 
              class="btn btn-secondary" 
              @click="cerrarModalNuevoPedido"
              :disabled="isCreatingPedido"
            >
              Cancelar
            </button>
            <button 
              type="button" 
              class="btn btn-primary" 
              @click="crearNuevoPedido"
              :disabled="isCreatingPedido || !nuevoPedidoForm.cliente_nombre || !nuevoPedidoForm.cliente_telefono"
            >
              <span v-if="isCreatingPedido">
                <span class="spinner-border spinner-border-sm me-2"></span>
                Creando...
              </span>
              <span v-else>
                <i class="fas fa-plus me-2"></i>
                Crear Pedido
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
      cajaStatus: {
        isOpen: false,
        hasRecordToday: false,
        record: null
      },

      // Modal de caja
      showAperturaModal: false,
      showCierreModal: false,
      isProcessingApertura: false,
      isProcessingCierre: false,
      openForm: {
        monto: ''
      },
      closeForm: {
        monto: ''
      },
      openFormError: '',
      closeFormError: '',

      // Movimientos
      showMovimientosModal: false,
      isLoadingMovimientos: false,
      fechaMovimientos: new Date().toISOString().split('T')[0], // Fecha actual por defecto
      movimientos: [],
      movimientosResumen: {
        totalRegistros: 0,
        montoTotal: 0,
        porMetodo: []
      },
      movimientosError: '',

      // Comprobante
      showComprobanteModal: false,
      comprobanteSeleccionado: null,
      comprobanteUrl: null,

      // Tipo de atención
      tipoAtencionActivo: 'P', // P=Presencial, D=Delivery, R=Recojo
      
      // Pedidos delivery/recojo
      pedidosDelivery: [],
      pedidosRecojo: [],
      isLoadingPedidos: false,

      // Modal nuevo pedido
      showModalNuevoPedido: false,
      isCreatingPedido: false,
      nuevoPedidoForm: {
        tipo: 'D',
        cliente_nombre: '',
        cliente_telefono: '',
        direccion_entrega: '',
        notas: ''
      },
      nuevoPedidoError: '',
      
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
    cajaEstaAbierta() {
      return this.cajaStatus.isOpen
    },

    cajaRegistro() {
      return this.cajaStatus.record
    },

    hoy() {
      return new Date().toISOString().split('T')[0]
    },

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
    await this.initializePage()
  },

  methods: {
    async initializePage() {
      try {
        this.isLoading = true
        await this.fetchCajaStatus()

        if (this.cajaEstaAbierta) {
          await this.cargarMesas()
          await this.cargarPedidosCola()
        }
      } finally {
        this.isLoading = false
      }
    },

    cambiarTipoAtencion(tipo) {
      this.tipoAtencionActivo = tipo
      
      if (tipo !== 'P') {
        this.cargarPedidosCola()
      }
    },

    async cargarPedidosCola() {
      try {
        this.isLoadingPedidos = true
        
        // Cargar pedidos delivery
        const responseDelivery = await axios.get('/api/pedidos-cola', {
          params: { tipo_atencion: 'D', estado: 'A' }
        })
        this.pedidosDelivery = responseDelivery.data.pedidos || []

        // Cargar pedidos recojo
        const responseRecojo = await axios.get('/api/pedidos-cola', {
          params: { tipo_atencion: 'R', estado: 'A' }
        })
        this.pedidosRecojo = responseRecojo.data.pedidos || []
      } catch (error) {
        console.error('Error al cargar pedidos:', error)
        this.showAlert('Error al cargar la cola de pedidos.', 'error')
      } finally {
        this.isLoadingPedidos = false
      }
    },

    abrirModalNuevoPedido(tipo) {
      this.nuevoPedidoForm = {
        tipo: tipo,
        cliente_nombre: '',
        cliente_telefono: '',
        direccion_entrega: '',
        notas: ''
      }
      this.nuevoPedidoError = ''
      this.showModalNuevoPedido = true
    },

    cerrarModalNuevoPedido() {
      this.showModalNuevoPedido = false
      this.nuevoPedidoForm = {
        tipo: 'D',
        cliente_nombre: '',
        cliente_telefono: '',
        direccion_entrega: '',
        notas: ''
      }
      this.nuevoPedidoError = ''
    },

    async crearNuevoPedido() {
      try {
        this.isCreatingPedido = true
        this.nuevoPedidoError = ''

        const response = await axios.post('/api/pedidos-cola', {
          tipo_atencion: this.nuevoPedidoForm.tipo,
          cliente_nombre: this.nuevoPedidoForm.cliente_nombre,
          cliente_telefono: this.nuevoPedidoForm.cliente_telefono,
          direccion_entrega: this.nuevoPedidoForm.direccion_entrega || null,
          notas: this.nuevoPedidoForm.notas || null
        })

        const pedido = response.data.pedido

        this.showAlert('Pedido creado correctamente', 'success')
        this.cerrarModalNuevoPedido()
        
        // Redirigir a la vista del pedido
        this.$router.push(`/caja/pedido/${pedido.id}`)
      } catch (error) {
        console.error('Error al crear pedido:', error)
        this.nuevoPedidoError = error.response?.data?.error || 'Error al crear el pedido'
        this.showAlert(this.nuevoPedidoError, 'error')
      } finally {
        this.isCreatingPedido = false
      }
    },

    verPedidoCola(pedido) {
      this.$router.push(`/caja/pedido/${pedido.id}`)
    },

    async fetchCajaStatus() {
      try {
        const response = await axios.get('/api/caja/status')
        this.cajaStatus = response.data

        if (!this.cajaStatus.isOpen && !this.cajaStatus.hasRecordToday) {
          this.abrirModalApertura()
        }
      } catch (error) {
        console.error('Error al verificar la caja:', error)
        this.showAlert('Error al verificar el estado de la caja.', 'error')
      }
    },

    async cargarMesas() {
      try {
        const response = await axios.get('/api/mesas')
        this.mesas = response.data
      } catch (error) {
        console.error('Error al cargar mesas:', error)
        this.showAlert('Error al cargar las mesas. Intenta nuevamente.', 'error')
      }
    },

    async refreshMesas() {
      if (!this.cajaEstaAbierta) {
        this.showAlert('Debes abrir la caja para administrar las mesas.', 'warning')
        return
      }

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

    abrirModalApertura() {
      if (this.cajaEstaAbierta) {
        this.showAlert('La caja ya se encuentra abierta.', 'info')
        return
      }

      this.openForm = { monto: '' }
      this.openFormError = ''
      this.showAperturaModal = true
    },

    cerrarModalApertura() {
      this.showAperturaModal = false
      this.openFormError = ''
    },

    async confirmarApertura() {
      if (this.isProcessingApertura) {
        return
      }

      const monto = Number(this.openForm.monto)
      if (Number.isNaN(monto)) {
        this.openFormError = 'Ingresa un monto válido.'
        return
      }

      if (monto < 0) {
        this.openFormError = 'El monto no puede ser negativo.'
        return
      }

      const shouldToggleLoading = !this.isLoading

      try {
        this.isProcessingApertura = true
        if (shouldToggleLoading) {
          this.isLoading = true
        }

        const response = await axios.post('/api/caja/abrir', {
          monto_apertura: monto
        })

        const caja = response.data?.caja || null

        this.cajaStatus = {
          isOpen: true,
          hasRecordToday: Boolean(caja),
          record: caja
        }

        await this.fetchCajaStatus()

        if (this.cajaEstaAbierta) {
          await this.cargarMesas()
        }

        this.showAperturaModal = false
        this.openForm = { monto: '' }
        this.showAlert('Caja abierta correctamente.', 'success')
      } catch (error) {
        const validationError = error.response?.data?.errors?.monto_apertura?.[0]
        const message = validationError || error.response?.data?.error || 'Error al abrir la caja.'
        this.openFormError = validationError || message
        this.showAlert(message, 'error')
      } finally {
        this.isProcessingApertura = false
        if (shouldToggleLoading) {
          this.isLoading = false
        }
      }
    },

    abrirModalCierre() {
      if (!this.cajaEstaAbierta) {
        this.showAlert('No hay una caja abierta para cerrar.', 'warning')
        return
      }

      this.closeForm = { monto: '' }
      this.closeFormError = ''
      this.showCierreModal = true
    },

    cerrarModalCierre() {
      this.showCierreModal = false
      this.closeFormError = ''
    },

    async confirmarCierre() {
      if (this.isProcessingCierre) {
        return
      }

      const monto = Number(this.closeForm.monto)
      if (Number.isNaN(monto)) {
        this.closeFormError = 'Ingresa un monto válido.'
        return
      }

      if (monto < 0) {
        this.closeFormError = 'El monto no puede ser negativo.'
        return
      }

      const shouldToggleLoading = !this.isLoading

      try {
        this.isProcessingCierre = true
        if (shouldToggleLoading) {
          this.isLoading = true
        }

        const response = await axios.post('/api/caja/cerrar', {
          monto_cierre: monto
        })

        const caja = response.data?.caja || null

        this.cajaStatus = {
          isOpen: false,
          hasRecordToday: Boolean(caja),
          record: caja
        }

        await this.fetchCajaStatus()
        this.showAlert('Caja cerrada correctamente.', 'success')
        this.showCierreModal = false
        this.closeForm = { monto: '' }
      } catch (error) {
        const validationError = error.response?.data?.errors?.monto_cierre?.[0]
        const message = validationError || error.response?.data?.error || 'Error al cerrar la caja.'
        this.closeFormError = validationError || message
        this.showAlert(message, 'error')
      } finally {
        this.isProcessingCierre = false
        if (shouldToggleLoading) {
          this.isLoading = false
        }
      }
    },

    formatCurrency(value) {
      const amount = Number(value)
      if (Number.isNaN(amount)) {
        return 'S/ 0.00'
      }

      return new Intl.NumberFormat('es-PE', {
        style: 'currency',
        currency: 'PEN'
      }).format(amount)
    },

    formatDateTime(value) {
      if (!value) {
        return '-'
      }

      const date = new Date(value)
      if (Number.isNaN(date.getTime())) {
        return '-'
      }

      return new Intl.DateTimeFormat('es-PE', {
        dateStyle: 'short',
        timeStyle: 'short'
      }).format(date)
    },

    formatHour(value) {
      if (!value) {
        return '-'
      }

      const date = new Date(value)
      if (Number.isNaN(date.getTime())) {
        return '-'
      }

      return new Intl.DateTimeFormat('es-PE', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false
      }).format(date)
    },

    abrirModalMovimientos() {
      this.movimientosError = ''
      this.fechaMovimientos = this.hoy // Resetear a la fecha actual
      this.showMovimientosModal = true
      this.fetchMovimientos()
    },

    cerrarModalMovimientos() {
      this.showMovimientosModal = false
      this.movimientosError = ''
    },

    verComprobante(codComprobante) {
      if (!codComprobante) {
        return
      }
      
      this.comprobanteSeleccionado = codComprobante
      this.comprobanteUrl = `/comprobante/${codComprobante}`
      this.showMovimientosModal = false
      this.showComprobanteModal = true
    },

    cerrarComprobante() {
      this.showComprobanteModal = false
      this.comprobanteSeleccionado = null
      this.comprobanteUrl = null
      this.showMovimientosModal = true
    },

    async fetchMovimientos() {
      try {
        this.isLoadingMovimientos = true
        const response = await axios.get('/api/caja/movimientos', {
          params: {
            fecha: this.fechaMovimientos
          }
        })
        const records = response.data?.records || []
        const summary = response.data?.summary || {}

        this.movimientos = records
        this.movimientosResumen = {
          totalRegistros: summary.total_registros || 0,
          montoTotal: summary.monto_total || 0,
          porMetodo: summary.por_metodo || []
        }
        this.movimientosError = ''
      } catch (error) {
        console.error('Error al cargar movimientos:', error)
        this.movimientos = []
        this.movimientosResumen = {
          totalRegistros: 0,
          montoTotal: 0,
          porMetodo: []
        }
        const message = error.response?.data?.error || 'No se pudieron cargar los movimientos.'
        this.movimientosError = message
        this.showAlert(message, 'error')
      } finally {
        this.isLoadingMovimientos = false
      }
    },

    seleccionarMesa(mesa) {
      if (!this.cajaEstaAbierta) {
        this.showAlert('Debes abrir la caja para gestionar las mesas.', 'warning')
        return
      }

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
      if (!this.cajaEstaAbierta) {
        this.showAlert('Debes abrir la caja para revisar los pedidos.', 'warning')
        return
      }

      // Aquí puedes navegar a la página del pedido
      this.$router.push(`/caja/pedido/${mesa.pedido_activo.id}`)
    },

    async logout() {
      if (this.isLoggingOut) return
      try {
        this.isLoggingOut = true
        
        // Ejecutar logout y esperar al menos 1 segundo para mostrar la animación
        await Promise.all([
          axios.post('/logout'),
          new Promise(resolve => setTimeout(resolve, 1000))
        ])
        
        // La animación permanece visible durante la navegación
        window.location.href = '/login'
      } catch (error) {
        console.error('Error al cerrar sesión:', error)
        this.showAlert('No se pudo cerrar sesión. Intenta nuevamente.', 'error')
        this.isLoggingOut = false
      }
      // No resetear isLoggingOut en finally para mantener visible el overlay durante la navegación
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

/* === LOGOUT LOADING OVERLAY === */
.logout-loading-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  width: 100vw;
  height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  z-index: 10000;
  opacity: 0;
  animation: logoutFadeIn 0.3s ease-out forwards;
}

.logout-loading-content {
  text-align: center;
  color: #ffffff;
}

.logout-loading-spinner {
  width: 64px;
  height: 64px;
  border: 4px solid rgba(255, 255, 255, 0.3);
  border-top: 4px solid #ffffff;
  border-radius: 50%;
  animation: logoutSpin 1s linear infinite;
  margin: 0 auto 28px;
}

.logout-loading-text {
  font-size: 1.75rem;
  font-weight: 700;
  margin-bottom: 8px;
  text-shadow: 0 2px 6px rgba(0, 0, 0, 0.25);
}

.logout-loading-subtext {
  font-size: 1rem;
  color: rgba(255, 255, 255, 0.85);
  margin: 0;
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

/* === CAJA STATUS === */
.caja-status-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
  border: 1px solid rgba(0, 0, 0, 0.05);
  transition: transform 0.3s ease;
}

.caja-status-card.caja-open {
  border-left: 6px solid #28a745;
}

.caja-status-card.caja-closed {
  border-left: 6px solid #dc3545;
}

.status-icon i {
  font-size: 2.5rem;
  color: #ffc107;
}

.status-title {
  font-size: 1.5rem;
  font-weight: 700;
  color: #212529;
}

.status-description {
  color: #495057;
  font-size: 1rem;
}

.status-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  margin-top: 0.75rem;
}

.status-meta span {
  display: inline-flex;
  align-items: center;
  font-size: 0.95rem;
  color: #6c757d;
}

.status-meta i {
  color: #ffc107;
}

.status-actions .btn {
  min-width: 180px;
}

.caja-closed-placeholder {
  background: white;
  border-radius: 16px;
  border: 1px dashed rgba(220, 53, 69, 0.4);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
}

.caja-closed-placeholder i {
  opacity: 0.4;
}

/* === MOVIMIENTOS MODAL === */
.date-filter-section {
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 16px;
  padding: 1.5rem;
  border: 1px solid rgba(0, 0, 0, 0.05);
}

.date-filter-section .form-label {
  font-size: 0.95rem;
  margin-bottom: 0.5rem;
  color: #495057;
}

.date-filter-section .form-control {
  border-radius: 12px;
  border: 2px solid #dee2e6;
  transition: all 0.3s ease;
}

.date-filter-section .form-control:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
}

.date-filter-section .btn {
  border-radius: 12px;
  font-weight: 600;
}

.summary-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  border: 1px solid rgba(0, 0, 0, 0.05);
  box-shadow: 0 6px 12px rgba(0, 0, 0, 0.05);
  text-align: center;
}

.summary-icon {
  width: 56px;
  height: 56px;
  border-radius: 14px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  margin-bottom: 1rem;
  background: rgba(0, 0, 0, 0.05);
}

.summary-label {
  text-transform: uppercase;
  letter-spacing: 0.05em;
  font-size: 0.85rem;
  color: #6c757d;
  margin-bottom: 0.35rem;
}

.summary-value {
  font-size: 1.6rem;
  font-weight: 700;
  color: #212529;
}

.metodo-card {
  background: linear-gradient(135deg, #ffffff 0%, #f8f9ff 100%);
  border-radius: 14px;
  padding: 1rem 1.25rem;
  border: 1px solid rgba(0, 0, 0, 0.05);
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.04);
  height: 100%;
}

.metodo-header {
  margin-bottom: 0.75rem;
}

.metodo-nombre {
  font-weight: 600;
  color: #212529;
}

.metodo-monto {
  font-size: 1.15rem;
  font-weight: 700;
  color: #0d6efd;
}

.movimientos-table .table {
  border-radius: 16px;
  overflow: hidden;
}

.movimientos-table thead {
  background: #0d6efd;
  color: white;
}

.movimientos-table th {
  border-bottom: none;
  font-weight: 600;
  text-transform: uppercase;
  font-size: 0.85rem;
}

.movimientos-table td {
  vertical-align: middle;
}

.movimientos-table tbody tr:last-child td {
  border-bottom: none;
}

/* === MESAS GRID === */
.mesas-grid {
  display: grid;
  /* Fixed maximum of 4 columns, each at least 240px. Center the grid with a max-width. */
  grid-template-columns: repeat(4, minmax(240px, 1fr));
  gap: 1.5rem;
  padding: 1rem 0;
  justify-content: center;
  max-width: 1200px;
  margin: 0 auto;
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

  .status-actions {
    width: 100%;
  }

  .status-actions .btn {
    width: 100%;
  }
  
  .mesas-grid {
    /* On medium/smaller screens limit to 2 columns */
    grid-template-columns: repeat(2, minmax(220px, 1fr));
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
    /* On very small screens show one column */
    grid-template-columns: repeat(1, minmax(220px, 1fr));
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

@keyframes logoutFadeIn {
  to { opacity: 1; }
}

@keyframes logoutSpin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
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

/* === TIPO ATENCION SIDEBAR === */
.atencion-sidebar {
  position: fixed;
  left: 0;
  top: 50%;
  transform: translateY(-50%);
  z-index: 1000;
  display: flex;
  flex-direction: column;
  gap: 0;
  background: white;
  border-radius: 0 16px 16px 0;
  box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
  overflow: hidden;
}

.atencion-sidebar-item {
  position: relative;
  background: white;
  border: none;
  padding: 1.2rem 1rem;
  cursor: pointer;
  transition: all 0.3s ease;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  min-width: 85px;
  border-left: 4px solid transparent;
}

.atencion-sidebar-item i {
  font-size: 1.8rem;
  color: #6c757d;
  transition: all 0.3s ease;
}

.atencion-label {
  font-size: 0.75rem;
  font-weight: 600;
  color: #6c757d;
  transition: all 0.3s ease;
  text-align: center;
}

.atencion-badge {
  position: absolute;
  top: 8px;
  right: 8px;
  background: #dc3545;
  color: white;
  border-radius: 10px;
  padding: 0.2rem 0.5rem;
  font-size: 0.7rem;
  font-weight: 700;
  min-width: 20px;
  text-align: center;
}

.atencion-sidebar-item:hover {
  background: #f8f9fa;
}

.atencion-sidebar-item:hover i,
.atencion-sidebar-item:hover .atencion-label {
  color: #ffc107;
}

.atencion-sidebar-item.active {
  background: linear-gradient(135deg, #fff3cd 0%, #fff 100%);
  border-left-color: #ffc107;
}

.atencion-sidebar-item.active i {
  color: #ffc107;
  transform: scale(1.1);
}

.atencion-sidebar-item.active .atencion-label {
  color: #212529;
  font-weight: 700;
}

.contenido-con-sidebar {
  margin-left: 95px;
}

.sidebar-divider {
  width: 100%;
  height: 1px;
  background: #dee2e6;
  margin: 0.5rem 0;
}

.sidebar-action:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.sidebar-logout:hover {
  background: #fff5f5 !important;
}

.sidebar-logout:hover i,
.sidebar-logout:hover .atencion-label {
  color: #dc3545 !important;
}

.sidebar-cerrar-caja:hover {
  background: #fff5f5 !important;
}

.sidebar-cerrar-caja:hover i,
.sidebar-cerrar-caja:hover .atencion-label {
  color: #dc3545 !important;
}

/* === PEDIDOS GRID === */
.pedidos-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
  gap: 1.5rem;
  padding: 1rem 0;
}

.pedido-card {
  background: white;
  border-radius: 16px;
  padding: 1.5rem;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
  border: 2px solid transparent;
  transition: all 0.3s ease;
  cursor: pointer;
}

.pedido-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
  border-color: #0d6efd;
}

.pedido-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
  padding-bottom: 1rem;
  border-bottom: 2px solid #f0f0f0;
}

.pedido-header h5 {
  font-size: 1.1rem;
  font-weight: 700;
  margin: 0;
  color: #212529;
}

.pedido-body {
  margin-bottom: 1rem;
}

.pedido-body p {
  margin-bottom: 0.5rem;
  color: #495057;
  font-size: 0.95rem;
}

.pedido-body p:last-child {
  margin-bottom: 0;
}

.pedido-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding-top: 1rem;
  border-top: 2px solid #f0f0f0;
}

.pedido-footer strong {
  color: #28a745;
  font-size: 1.1rem;
}

/* === RESPONSIVE PEDIDOS === */
@media (max-width: 768px) {
  .pedidos-grid {
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1rem;
  }
  
  .atencion-card {
    min-height: 180px;
    padding: 1.5rem 1rem;
  }
  
  .atencion-icon {
    font-size: 2.5rem;
  }
}
</style>