<template>
    <div>
        <!-- Modal de Movimientos -->
        <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div class="modal-content shadow-lg">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-list-alt me-2"></i>
                            Movimientos del día
                        </h5>
                        <button type="button" class="btn-close" @click="cerrarModal"
                            :disabled="isLoadingMovimientos"></button>
                    </div>

                    <div class="modal-body p-0 d-flex flex-column" style="height: 80vh;">
                        <!-- Top Toolbar: Status + Date Filter -->
                        <div class="bg-light border-bottom p-3">
                            <div class="row g-3 align-items-center">
                                <!-- Caja Status (Compact) -->
                                <div class="col-lg-5">
                                    <div class="d-flex align-items-center"
                                        :class="cajaEstaAbierta ? 'text-success' : 'text-warning'">
                                        <i :class="cajaEstaAbierta ? 'fas fa-cash-register' : 'fas fa-lock'"
                                            class="fs-4 me-2"></i>
                                        <div>
                                            <div class="fw-bold">{{ cajaStatusText }}</div>
                                            <small class="text-muted" v-if="cajaRegistro">
                                                {{ cajaEstaAbierta ? 'Apertura:' : 'Cierre:' }}
                                                {{ formatDateTime(cajaEstaAbierta ? cajaRegistro.datetime_apertura :
                                                    cajaRegistro.datetime_cierre ||
                                                cajaRegistro.datetime_apertura) }}
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Date Filter (Compact) -->
                                <div class="col-lg-7">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <div class="input-group" style="max-width: 300px;">
                                            <span class="input-group-text bg-white border-end-0">
                                                <i class="fas fa-calendar-alt text-muted"></i>
                                            </span>
                                            <input type="date" class="form-control border-start-0 ps-0"
                                                v-model="fechaMovimientos" :disabled="isLoadingMovimientos" :max="hoy">
                                        </div>
                                        <button class="btn btn-primary" @click="fetchMovimientos"
                                            :disabled="isLoadingMovimientos">
                                            <i class="fas"
                                                :class="isLoadingMovimientos ? 'fa-spinner fa-spin' : 'fa-search'"></i>
                                            <span class="d-none d-sm-inline ms-2">Consultar</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Content Area -->
                        <div class="flex-grow-1 overflow-hidden d-flex flex-column bg-white">
                            <div v-if="isLoadingMovimientos"
                                class="d-flex flex-column align-items-center justify-content-center h-100">
                                <div class="spinner-border text-primary mb-3" role="status"></div>
                                <p class="text-muted">Cargando movimientos...</p>
                            </div>

                            <div v-else-if="movimientosError" class="p-4">
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    {{ movimientosError }}
                                </div>
                            </div>

                            <template v-else>
                                <!-- Compact Summary Stats -->
                                <div class="p-3 border-bottom bg-white">
                                    <div class="row g-2">
                                        <!-- General Stats -->
                                        <div class="col-6 col-md-3 col-lg-2">
                                            <div class="p-2 border rounded bg-light h-100">
                                                <div class="small text-muted text-uppercase fw-bold mb-1">Total Cobrado
                                                </div>
                                                <div class="h5 mb-0 text-success">{{
                                                    formatCurrency(movimientosResumen.montoTotal) }}</div>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3 col-lg-2">
                                            <div class="p-2 border rounded bg-light h-100">
                                                <div class="small text-muted text-uppercase fw-bold mb-1">Operaciones
                                                </div>
                                                <div class="h5 mb-0 text-dark">{{ movimientosResumen.totalRegistros }}
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Document Types Breakdown -->
                                        <div class="col-12">
                                            <h6 class="small text-muted fw-bold mb-2">Comprobantes</h6>
                                            <div class="row g-2">
                                                <div class="col-md-4" v-for="(data, tipo) in resumenComprobantes"
                                                    :key="tipo">
                                                    <div class="p-2 border rounded h-100 d-flex align-items-center"
                                                        :class="`bg-${data.color}-subtle border-${data.color}-subtle`">
                                                        <div class="me-2 rounded-circle p-2 d-flex align-items-center justify-content-center"
                                                            :class="`bg-${data.color} text-white`"
                                                            style="width: 32px; height: 32px;">
                                                            <i :class="data.icon" class="small"></i>
                                                        </div>
                                                        <div class="overflow-hidden">
                                                            <div class="small fw-bold text-truncate"
                                                                :class="`text-${data.color}`">{{ tipo }}s</div>
                                                            <div class="d-flex align-items-baseline gap-1">
                                                                <span class="fw-bold">{{ data.cantidad }}</span>
                                                                <span class="small text-muted">({{
                                                                    formatCurrency(data.total) }})</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Payment Methods Breakdown -->
                                        <div class="col-12 mt-2">
                                            <h6 class="small text-muted fw-bold mb-2">Métodos de Pago</h6>
                                            <div class="row g-2">
                                                <div class="col-md-4" v-for="(data, metodo) in resumenMetodosPago"
                                                    :key="metodo">
                                                    <div
                                                        class="p-2 border rounded h-100 d-flex align-items-center bg-white">
                                                        <div class="me-2 rounded-circle p-2 d-flex align-items-center justify-content-center"
                                                            :class="`bg-${data.color} text-white`"
                                                            style="width: 32px; height: 32px;">
                                                            <i :class="data.icon" class="small"></i>
                                                        </div>
                                                        <div class="overflow-hidden">
                                                            <div class="small fw-bold text-truncate text-dark">{{ metodo
                                                                }}</div>
                                                            <div class="d-flex align-items-baseline gap-1">
                                                                <span class="fw-bold">{{ data.cantidad }} ops</span>
                                                                <span class="small text-muted fw-bold text-primary">{{
                                                                    formatCurrency(data.total)
                                                                    }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Table (Scrollable) -->
                                <div class="flex-grow-1 overflow-auto p-0">
                                    <table class="table table-striped table-hover mb-0 align-middle sticky-header">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Hora</th>
                                                <th>Comprobante</th>
                                                <th>Tipo</th>
                                                <th>Atención</th>
                                                <th>Método</th>
                                                <th class="text-end">Monto</th>
                                                <th>Usuario</th>
                                                <th class="text-center">SUNAT</th>
                                                <th class="text-center pe-4">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="mov in movimientos" :key="mov.id">
                                                <td class="ps-4 text-nowrap">{{ formatHour(mov.fecha) }}</td>
                                                <td class="fw-bold">{{ mov.cod_comprobante || '—' }}</td>
                                                <td>
                                                    <span class="badge bg-light text-dark border">
                                                        {{ mov.tipo_comprobante_nombre || 'Nota de Venta' }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge"
                                                        :class="getTipoAtencionBadgeClass(mov.tipo_atencion)">
                                                        <i :class="getTipoAtencionIcon(mov.tipo_atencion)"
                                                            class="me-1"></i>
                                                        {{ getTipoAtencionTexto(mov.tipo_atencion) }}
                                                    </span>
                                                </td>
                                                <td>{{ mov.metodo_pago }}</td>
                                                <td class="text-end fw-bold">{{ formatCurrency(mov.monto) }}</td>
                                                <td class="small text-muted">{{ mov.usuario || '—' }}</td>
                                                
                                                <!-- SUNAT Status -->
                                                <td class="text-center">
                                                    <div v-if="['B', 'F'].includes(mov.tipo_comprobante)">
                                                        <i v-if="mov.sunat_success" 
                                                           class="fas fa-check-circle text-success fs-5" 
                                                           title="Enviado a SUNAT correctamente"></i>
                                                        <i v-else-if="mov.sunat_error" 
                                                           class="fas fa-exclamation-circle text-danger fs-5" 
                                                           :title="mov.sunat_error"></i>
                                                        <i v-else 
                                                           class="fas fa-clock text-secondary fs-5" 
                                                           title="Pendiente o no enviado"></i>
                                                    </div>
                                                    <span v-else class="text-muted small">—</span>
                                                </td>

                                                <td class="text-center pe-4">
                                                    <button class="btn btn-sm btn-outline-primary"
                                                        @click="verComprobante(mov.cod_comprobante)"
                                                        :disabled="!mov.cod_comprobante" title="Ver Comprobante">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="movimientos.length === 0">
                                                <td colspan="8" class="text-center py-5 text-muted">
                                                    <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                                                    <p class="mb-0">No se encontraron movimientos para esta fecha.</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" @click="cerrarModal"
                            :disabled="isLoadingMovimientos">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Comprobante -->
        <div v-if="showComprobanteModal" class="modal fade show d-block" tabindex="-1"
            style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-fullscreen">
                <div class="modal-content shadow-lg">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-file-invoice me-2"></i>
                            Comprobante: {{ comprobanteSeleccionado }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="cerrarComprobante"></button>
                    </div>

                    <div class="modal-body p-0">
                        <iframe v-if="comprobanteUrl" :src="comprobanteUrl"
                            style="width: 100%; height: calc(100vh - 120px); border: none;" frameborder="0"></iframe>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="cerrarComprobante">
                            <i class="fas fa-arrow-left me-2"></i>
                            Regresar
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
    name: 'MovimientosModal',
    props: {
        show: {
            type: Boolean,
            required: true
        },
        cajaStatus: {
            type: Object,
            required: true
        }
    },
    emits: ['close'],
    data() {
        return {
            isLoadingMovimientos: false,
            fechaMovimientos: (() => {
                const now = new Date();
                return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
            })(),
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
        }
    },
    computed: {
        cajaEstaAbierta() {
            return this.cajaStatus.isOpen
        },
        cajaRegistro() {
            return this.cajaStatus.record
        },
        cajaStatusText() {
            if (this.cajaEstaAbierta) {
                return 'Caja abierta';
            }
            if (this.cajaStatus.hasRecordToday) {
                return 'Caja cerrada';
            }
            return 'Caja pendiente de apertura';
        },
        hoy() {
            const now = new Date();
            return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
        },
        resumenComprobantes() {
            const resumen = {
                'Boleta': { cantidad: 0, total: 0, icon: 'fas fa-file-invoice', color: 'info' },
                'Factura': { cantidad: 0, total: 0, icon: 'fas fa-file-invoice-dollar', color: 'primary' },
                'Nota de Venta': { cantidad: 0, total: 0, icon: 'fas fa-sticky-note', color: 'warning' }
            };

            this.movimientos.forEach(mov => {
                const tipo = mov.tipo_comprobante_nombre || 'Nota de Venta';
                if (resumen[tipo]) {
                    resumen[tipo].cantidad++;
                    resumen[tipo].total += Number(mov.monto);
                }
            });

            return resumen;
        },
        resumenMetodosPago() {
            const resumen = {};

            this.movimientos.forEach(mov => {
                const metodo = mov.metodo_pago || 'Desconocido';
                if (!resumen[metodo]) {
                    let icon = 'fas fa-money-bill-wave';
                    let color = 'secondary';

                    if (metodo.includes('YAPE') || metodo.includes('PLIN')) {
                        icon = 'fas fa-mobile-alt';
                        color = 'primary';
                    } else if (metodo.includes('POS') || metodo.includes('TARJETA')) {
                        icon = 'fas fa-credit-card';
                        color = 'info';
                    } else if (metodo.includes('EFECTIVO')) {
                        icon = 'fas fa-coins';
                        color = 'success';
                    }

                    resumen[metodo] = {
                        cantidad: 0,
                        total: 0,
                        icon,
                        color
                    };
                }

                resumen[metodo].cantidad++;
                resumen[metodo].total += Number(mov.monto);
            });

            return resumen;
        }
    },
    watch: {
        show(newVal) {
            if (newVal) {
                this.fetchMovimientos();
            }
        }
    },
    methods: {
        cerrarModal() {
            this.$emit('close');
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
            } finally {
                this.isLoadingMovimientos = false
            }
        },

        verComprobante(codComprobante) {
            if (!codComprobante) return;
            this.comprobanteSeleccionado = codComprobante;
            this.comprobanteUrl = `/comprobante/${codComprobante}`;
            this.showComprobanteModal = true;
        },

        cerrarComprobante() {
            this.showComprobanteModal = false;
            this.comprobanteSeleccionado = null;
            this.comprobanteUrl = null;
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
            return date.toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' })
        },

        getTipoAtencionBadgeClass(tipo) {
            if (tipo === 'P') return 'bg-danger'
            if (tipo === 'D') return 'bg-info'
            if (tipo === 'R') return 'bg-warning'
            return 'bg-secondary'
        },

        getTipoAtencionIcon(tipo) {
            if (tipo === 'P') return 'fas fa-users'
            if (tipo === 'D') return 'fas fa-motorcycle'
            if (tipo === 'R') return 'fas fa-shopping-bag'
            return 'fas fa-question'
        },

        getTipoAtencionTexto(tipo) {
            if (tipo === 'P') return 'Mesa'
            if (tipo === 'D') return 'Delivery'
            if (tipo === 'R') return 'Recojo'
            return 'Desconocido'
        }
    }
}
</script>

<style scoped>
/* Reuse styles from CajaPage if needed, but Bootstrap classes handle most */
.sticky-header th {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #f8f9fa;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}
</style>
