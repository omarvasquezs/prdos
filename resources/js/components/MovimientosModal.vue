<template>
    <div>
        <!-- Modal de Movimientos -->
        <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content shadow-lg">
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title fs-6">
                            <i class="fas fa-list-alt me-2"></i>
                            Movimientos del día
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="cerrarModal"
                            :disabled="isLoadingMovimientos"></button>
                    </div>

                    <div class="modal-body p-0 d-flex flex-column bg-light">
                        <!-- Top Toolbar: Status + Date Filter -->
                        <div class="bg-white border-bottom px-3 py-2">
                            <div class="row g-2 align-items-center">
                                <!-- Caja Status & Info -->
                                <div class="col-lg-8">
                                    <div class="d-flex align-items-center flex-wrap gap-3">
                                        <!-- Status Badge -->
                                        <div class="d-flex align-items-center"
                                            :class="cajaEstaAbierta ? 'text-success' : 'text-warning'">
                                            <i :class="cajaEstaAbierta ? 'fas fa-cash-register' : 'fas fa-lock'"
                                                class="fs-5 me-2"></i>
                                            <span class="fw-bold">{{ cajaStatusText }}</span>
                                        </div>

                                        <div class="vr mx-1 d-none d-md-block"></div>

                                        <!-- Opening Info -->
                                        <div v-if="cajaRegistro"
                                            class="d-flex align-items-center gap-3 text-muted small">
                                            <div>
                                                <i class="far fa-clock me-1"></i>
                                                {{ cajaEstaAbierta ? 'Apertura:' : 'Cierre:' }}
                                                <strong>{{ formatDateTime(cajaEstaAbierta ?
                                                    cajaRegistro.datetime_apertura :
                                                    cajaRegistro.datetime_cierre || cajaRegistro.datetime_apertura)
                                                }}</strong>
                                            </div>

                                            <div v-if="cajaRegistro.monto_apertura !== undefined">
                                                <i class="fas fa-coins me-1"></i>
                                                Monto Inicial:
                                                <strong class="text-dark">{{ formatCurrency(cajaRegistro.monto_apertura)
                                                }}</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Date Filter -->
                                <div class="col-lg-4">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <div class="input-group input-group-sm" style="max-width: 250px;">
                                            <span class="input-group-text bg-white border-end-0">
                                                <i class="fas fa-calendar-alt text-muted"></i>
                                            </span>
                                            <input type="date" class="form-control border-start-0 ps-0"
                                                v-model="fechaMovimientos" :disabled="isLoadingMovimientos" :max="hoy">
                                        </div>
                                        <button class="btn btn-sm btn-primary" @click="fetchMovimientos"
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
                        <div class="flex-grow-1 overflow-hidden d-flex flex-column">
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
                                <div class="px-3 py-2 bg-white border-bottom">
                                    <div class="row g-2">
                                        <!-- Key Metrics -->
                                        <div class="col-md-3 col-xl-2">
                                            <div class="d-flex gap-2 h-100">
                                                <div
                                                    class="p-2 border rounded bg-light flex-fill d-flex flex-column justify-content-center">
                                                    <div class="small text-muted text-uppercase fw-bold"
                                                        style="font-size: 0.7rem;">Total Cobrado</div>
                                                    <div class="h5 mb-0 text-success fw-bold">{{
                                                        formatCurrency(movimientosResumen.montoTotal) }}</div>
                                                </div>
                                                <div
                                                    class="p-2 border rounded bg-light flex-fill d-flex flex-column justify-content-center">
                                                    <div class="small text-muted text-uppercase fw-bold"
                                                        style="font-size: 0.7rem;">Ops</div>
                                                    <div class="h5 mb-0 text-dark fw-bold">{{
                                                        movimientosResumen.totalRegistros }}</div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Breakdown Section -->
                                        <div class="col-md-9 col-xl-10">
                                            <div class="d-flex flex-wrap gap-2 h-100">
                                                <!-- Comprobantes Group -->
                                                <div class="d-flex gap-2 flex-wrap border-end pe-2 me-1">
                                                    <div v-for="(data, tipo) in resumenComprobantes" :key="tipo"
                                                        class="d-flex align-items-center p-1 border rounded bg-white"
                                                        style="min-width: 140px;">
                                                        <div class="rounded-circle p-1 d-flex align-items-center justify-content-center me-2"
                                                            :class="`bg-${data.color} text-white`"
                                                            style="width: 24px; height: 24px;">
                                                            <i :class="data.icon" style="font-size: 0.7rem;"></i>
                                                        </div>
                                                        <div class="lh-1">
                                                            <div class="fw-bold" style="font-size: 0.75rem;">{{ tipo }}s
                                                            </div>
                                                            <div class="d-flex gap-1" style="font-size: 0.7rem;">
                                                                <span class="fw-bold">{{ data.cantidad }}</span>
                                                                <span class="text-muted">({{ formatCurrency(data.total)
                                                                }})</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Metodos Pago Group -->
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <div v-for="(data, metodo) in resumenMetodosPago" :key="metodo"
                                                        class="d-flex align-items-center p-1 border rounded bg-white"
                                                        style="min-width: 130px;">
                                                        <div class="rounded-circle p-1 d-flex align-items-center justify-content-center me-2"
                                                            :class="`bg-${data.color} text-white`"
                                                            style="width: 24px; height: 24px;">
                                                            <i :class="data.icon" style="font-size: 0.7rem;"></i>
                                                        </div>
                                                        <div class="lh-1">
                                                            <div class="fw-bold text-truncate"
                                                                style="font-size: 0.75rem; max-width: 80px;">{{ metodo
                                                                }}</div>
                                                            <div class="d-flex gap-1" style="font-size: 0.7rem;">
                                                                <span class="fw-bold">{{ data.cantidad }}</span>
                                                                <span class="text-primary fw-bold">{{
                                                                    formatCurrency(data.total) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Table (Scrollable) -->
                                <div class="flex-grow-1 overflow-auto bg-white">
                                    <table
                                        class="table table-sm table-striped table-hover mb-0 align-middle sticky-header">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4 py-2">Hora</th>
                                                <th class="py-2">Comprobante</th>
                                                <th class="py-2">Tipo</th>
                                                <th class="py-2">Atención</th>
                                                <th class="py-2">Método</th>
                                                <th class="text-end py-2">Monto</th>
                                                <th class="py-2">Usuario</th>
                                                <th class="text-center py-2">SUNAT</th>
                                                <th class="text-center pe-4 py-2">Acciones</th>
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
                                                            class="fas fa-check-circle text-success"
                                                            title="Enviado a SUNAT correctamente"></i>
                                                        <i v-else-if="mov.sunat_error"
                                                            class="fas fa-exclamation-circle text-danger"
                                                            :title="mov.sunat_error"></i>
                                                        <i v-else class="fas fa-clock text-secondary"
                                                            title="Pendiente o no enviado"></i>
                                                    </div>
                                                    <span v-else class="text-muted small">—</span>
                                                </td>

                                                <td class="text-center pe-4">
                                                    <button class="btn btn-sm btn-outline-primary py-0"
                                                        @click="verComprobante(mov.cod_comprobante)"
                                                        :disabled="!mov.cod_comprobante" title="Ver Comprobante">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <tr v-if="movimientos.length === 0">
                                                <td colspan="9" class="text-center py-5 text-muted">
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

                    <div class="modal-footer py-1 bg-light">
                        <button type="button" class="btn btn-sm btn-secondary" @click="cerrarModal"
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
                    <div class="modal-header bg-primary text-white py-2">
                        <h5 class="modal-title fs-6">
                            <i class="fas fa-file-invoice me-2"></i>
                            Comprobante: {{ comprobanteSeleccionado }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" @click="cerrarComprobante"></button>
                    </div>

                    <div class="modal-body p-0 bg-light">
                        <iframe v-if="comprobanteUrl" :src="comprobanteUrl"
                            style="width: 100%; height: 100%; border: none;" frameborder="0"></iframe>
                    </div>

                    <div class="modal-footer py-1">
                        <button type="button" class="btn btn-sm btn-secondary" @click="cerrarComprobante">
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

/* Custom scrollbar for table container */
.overflow-auto::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.overflow-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.overflow-auto::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

.overflow-auto::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}
</style>
