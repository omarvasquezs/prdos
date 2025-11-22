<template>
    <div class="contador-home">
        <h2 class="mb-4">
            <i class="fas fa-chart-line me-2"></i>
            Panel de Contador
        </h2>

        <div class="row g-4">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-body p-4 d-flex flex-column align-items-center text-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-4 mb-3">
                            <i class="fas fa-cash-register fa-3x text-primary"></i>
                        </div>
                        <h4 class="card-title mb-2">Movimientos del Día</h4>
                        <p class="card-text text-muted mb-4">
                            Consulta el detalle de las operaciones, ventas y métodos de pago del día actual.
                        </p>
                        <button class="btn btn-primary btn-lg w-100 mt-auto" @click="showMovimientosModal = true">
                            <i class="fas fa-list-alt me-2"></i>
                            Ver Movimientos
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <MovimientosModal :show="showMovimientosModal" :caja-status="cajaStatus"
            @close="showMovimientosModal = false" />
    </div>
</template>

<script>
import axios from 'axios';
import MovimientosModal from '@/components/MovimientosModal.vue';

export default {
    name: 'ContadorHome',
    components: {
        MovimientosModal
    },
    data() {
        return {
            showMovimientosModal: false,
            cajaStatus: {
                isOpen: false,
                hasRecordToday: false,
                record: null
            }
        };
    },
    async mounted() {
        await this.fetchCajaStatus();
    },
    methods: {
        async fetchCajaStatus() {
            try {
                const response = await axios.get('/api/caja/status');
                this.cajaStatus = response.data;
            } catch (error) {
                console.error('Error al cargar estado de caja:', error);
            }
        }
    }
};
</script>

<style scoped>
.contador-home {
    animation: fadeIn 0.3s;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
