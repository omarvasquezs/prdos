<template>
    <div>
        <!-- Modal de Historial de Caja -->
        <div v-if="show" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
            <div class="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div class="modal-content shadow-lg">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">
                            <i class="fas fa-history me-2"></i>
                            Historial de Caja
                        </h5>
                        <button type="button" class="btn-close" @click="cerrarModal" :disabled="isLoading"></button>
                    </div>

                    <div class="modal-body p-0 d-flex flex-column" style="height: 80vh;">
                        <!-- Content Area -->
                        <div class="flex-grow-1 overflow-hidden d-flex flex-column bg-white">
                            <div v-if="isLoading"
                                class="d-flex flex-column align-items-center justify-content-center h-100">
                                <div class="spinner-border text-info mb-3" role="status"></div>
                                <p class="text-muted">Cargando historial...</p>
                            </div>

                            <div v-else-if="error" class="p-4">
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    {{ error }}
                                </div>
                            </div>

                            <template v-else>
                                <!-- Table (Scrollable) -->
                                <div class="flex-grow-1 overflow-auto p-0">
                                    <table class="table table-striped table-hover mb-0 align-middle sticky-header">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="ps-4">Fecha Apertura</th>
                                                <th>Usuario Apertura</th>
                                                <th class="text-end">Monto Apertura</th>
                                                <th>Fecha Cierre</th>
                                                <th>Usuario Cierre</th>
                                                <th class="text-end pe-4">Monto Cierre</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template v-for="caja in historial" :key="caja.id">
                                                <tr @click="toggleDetails(caja.id)" style="cursor: pointer;">
                                                    <td class="ps-4 text-nowrap">
                                                        <i class="fas fa-chevron-right me-2 transition-icon" :class="{ 'rotate-90': expandedRows.includes(caja.id) }"></i>
                                                        {{ formatDateTime(caja.datetime_apertura) }}
                                                    </td>
                                                    <td>{{ caja.usuario_apertura }}</td>
                                                    <td class="text-end fw-bold text-success">{{ formatCurrency(caja.monto_apertura) }}</td>
                                                    <td>{{ formatDateTime(caja.datetime_cierre) }}</td>
                                                    <td>{{ caja.usuario_cierre || '-' }}</td>
                                                    <td class="text-end fw-bold text-danger pe-4">{{ caja.monto_cierre !== null ? formatCurrency(caja.monto_cierre) : '-' }}</td>
                                                </tr>
                                                <!-- Details Row -->
                                                <tr v-if="expandedRows.includes(caja.id)" class="bg-light">
                                                    <td colspan="6" class="p-0">
                                                        <div class="p-3 border-bottom">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h6 class="text-success mb-2"><i class="fas fa-cash-register me-2"></i>Detalle Apertura</h6>
                                                                    <ul class="list-group list-group-sm">
                                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                            <span><i class="fas fa-money-bill me-2 text-muted"></i>Billetes</span>
                                                                            <span class="fw-bold">{{ formatCurrency(caja.monto_apertura_billetes || 0) }}</span>
                                                                        </li>
                                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                            <span><i class="fas fa-coins me-2 text-muted"></i>Monedas</span>
                                                                            <span class="fw-bold">{{ formatCurrency(caja.monto_apertura_monedas || 0) }}</span>
                                                                        </li>
                                                                        <li class="list-group-item d-flex justify-content-between align-items-center bg-success-subtle">
                                                                            <strong>Total</strong>
                                                                            <strong class="text-success">{{ formatCurrency(caja.monto_apertura) }}</strong>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                                <div class="col-md-6" v-if="caja.monto_cierre !== null">
                                                                    <h6 class="text-danger mb-2"><i class="fas fa-lock me-2"></i>Detalle Cierre</h6>
                                                                    <ul class="list-group list-group-sm">
                                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                            <span><i class="fas fa-money-bill me-2 text-muted"></i>Billetes</span>
                                                                            <span class="fw-bold">{{ formatCurrency(caja.monto_cierre_billetes || 0) }}</span>
                                                                        </li>
                                                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                                                            <span><i class="fas fa-coins me-2 text-muted"></i>Monedas</span>
                                                                            <span class="fw-bold">{{ formatCurrency(caja.monto_cierre_monedas || 0) }}</span>
                                                                        </li>
                                                                        <li class="list-group-item d-flex justify-content-between align-items-center bg-danger-subtle">
                                                                            <strong>Total</strong>
                                                                            <strong class="text-danger">{{ formatCurrency(caja.monto_cierre) }}</strong>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                                <div class="col-md-6" v-else>
                                                                    <div class="alert alert-warning mb-0">
                                                                        <i class="fas fa-clock me-2"></i>Caja aún abierta
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </template>
                                            <tr v-if="historial.length === 0">
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    <i class="fas fa-history fa-3x mb-3 opacity-25"></i>
                                                    <p class="mb-0">No se encontró historial de caja.</p>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <div class="p-3 border-top bg-light d-flex justify-content-between align-items-center" v-if="pagination.total > 0">
                                    <small class="text-muted">
                                        Mostrando {{ (pagination.current_page - 1) * pagination.per_page + 1 }} a {{ Math.min(pagination.current_page * pagination.per_page, pagination.total) }} de {{ pagination.total }} registros
                                    </small>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination pagination-sm mb-0">
                                            <li class="page-item" :class="{ disabled: pagination.current_page === 1 }">
                                                <button class="page-link" @click="changePage(pagination.current_page - 1)">Anterior</button>
                                            </li>
                                            <li class="page-item" :class="{ disabled: pagination.current_page === pagination.last_page }">
                                                <button class="page-link" @click="changePage(pagination.current_page + 1)">Siguiente</button>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" @click="cerrarModal"
                            :disabled="isLoading">
                            Cerrar
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
    name: 'HistorialCajaModal',
    props: {
        show: {
            type: Boolean,
            required: true
        }
    },
    emits: ['close'],
    data() {
        return {
            isLoading: false,
            historial: [],
            error: '',
            expandedRows: [],
            pagination: {
                current_page: 1,
                last_page: 1,
                total: 0,
                per_page: 10
            }
        }
    },
    watch: {
        show(newVal) {
            if (newVal) {
                this.fetchHistorial();
            }
        }
    },
    methods: {
        cerrarModal() {
            this.$emit('close');
        },

        async fetchHistorial(page = 1) {
            try {
                this.isLoading = true
                const response = await axios.get('/api/caja/history', {
                    params: {
                        page: page,
                        limit: this.pagination.per_page
                    }
                })
                
                this.historial = response.data.data
                this.pagination = {
                    current_page: response.data.current_page,
                    last_page: response.data.last_page,
                    total: response.data.total,
                    per_page: 10 // Assuming backend default or fixed
                }
                this.error = ''
            } catch (error) {
                console.error('Error al cargar historial:', error)
                this.error = 'No se pudo cargar el historial de caja.'
            } finally {
                this.isLoading = false
            }
        },

        changePage(page) {
            if (page >= 1 && page <= this.pagination.last_page) {
                this.fetchHistorial(page);
            }
        },

        toggleDetails(id) {
            const index = this.expandedRows.indexOf(id);
            if (index === -1) {
                this.expandedRows.push(id);
            } else {
                this.expandedRows.splice(index, 1);
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
        }
    }
}
</script>

<style scoped>
.sticky-header th {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #f8f9fa;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}
.transition-icon {
    transition: transform 0.2s ease;
}
.rotate-90 {
    transform: rotate(90deg);
}
</style>
