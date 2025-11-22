<template>
    <div class="exportar-page h-100 d-flex flex-column">
        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 text-primary">
                <i class="fas fa-file-export me-2"></i>
                Exportar Datos
            </h4>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h5 class="card-title mb-4">Configuración de Exportación</h5>

                        <form @submit.prevent="handleExport">
                            <!-- Format Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-bold mb-3">Formato de Archivo</label>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <div class="form-check custom-card-radio">
                                            <input class="form-check-input" type="radio" name="format" id="formatCsv"
                                                value="csv" v-model="exportConfig.format">
                                            <label class="form-check-label w-100 p-3 border rounded text-center"
                                                for="formatCsv">
                                                <i class="fas fa-file-csv fa-2x mb-2 text-success"></i>
                                                <div class="fw-bold">CSV</div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check custom-card-radio">
                                            <input class="form-check-input" type="radio" name="format" id="formatXlsx"
                                                value="xlsx" v-model="exportConfig.format">
                                            <label class="form-check-label w-100 p-3 border rounded text-center"
                                                for="formatXlsx">
                                                <i class="fas fa-file-excel fa-2x mb-2 text-success"></i>
                                                <div class="fw-bold">Excel (XLSX)</div>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check custom-card-radio">
                                            <input class="form-check-input" type="radio" name="format" id="formatPdf"
                                                value="pdf" v-model="exportConfig.format">
                                            <label class="form-check-label w-100 p-3 border rounded text-center"
                                                for="formatPdf">
                                                <i class="fas fa-file-pdf fa-2x mb-2 text-danger"></i>
                                                <div class="fw-bold">PDF</div>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Date Range Selection -->
                            <div class="mb-4">
                                <label class="form-label fw-bold mb-3">Rango de Fechas</label>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <select class="form-select mb-3" v-model="exportConfig.dateRangeType">
                                            <option value="today">Día en curso</option>
                                            <option value="week">Semana actual</option>
                                            <option value="month">Mes actual</option>
                                            <option value="year">Año actual</option>
                                            <option value="custom">Rango personalizado</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6" v-if="exportConfig.dateRangeType === 'custom'">
                                        <label class="form-label small text-muted">Desde</label>
                                        <input type="date" class="form-control" v-model="exportConfig.startDate">
                                    </div>
                                    <div class="col-md-6" v-if="exportConfig.dateRangeType === 'custom'">
                                        <label class="form-label small text-muted">Hasta</label>
                                        <input type="date" class="form-control" v-model="exportConfig.endDate">
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary btn-lg" :disabled="isExporting">
                                    <span v-if="isExporting">
                                        <i class="fas fa-spinner fa-spin me-2"></i>
                                        Generando archivo...
                                    </span>
                                    <span v-else>
                                        <i class="fas fa-download me-2"></i>
                                        Exportar Datos
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ExportarPage',
    data() {
        return {
            isExporting: false,
            exportConfig: {
                format: 'xlsx',
                dateRangeType: 'today',
                startDate: '',
                endDate: ''
            }
        }
    },
    methods: {
        async handleExport() {
            this.isExporting = true;

            try {
                // Construct Query Parameters
                const params = new URLSearchParams({
                    format: this.exportConfig.format,
                    dateRangeType: this.exportConfig.dateRangeType
                });

                if (this.exportConfig.dateRangeType === 'custom') {
                    if (!this.exportConfig.startDate || !this.exportConfig.endDate) {
                        alert('Por favor seleccione las fechas de inicio y fin.');
                        this.isExporting = false;
                        return;
                    }
                    params.append('startDate', this.exportConfig.startDate);
                    params.append('endDate', this.exportConfig.endDate);
                }

                // Trigger Download
                const url = `/api/export?${params.toString()}`;

                // Use a hidden link to trigger download to avoid replacing current page
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', ''); // Browser will handle filename from Content-Disposition
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

            } catch (error) {
                console.error('Error exporting:', error);
                alert('Hubo un error al intentar exportar los datos.');
            } finally {
                // Add a small delay to reset the button state
                setTimeout(() => {
                    this.isExporting = false;
                }, 1000);
            }
        }
    }
}
</script>

<style scoped>
.custom-card-radio .form-check-input {
    display: none;
}

.custom-card-radio .form-check-label {
    cursor: pointer;
    transition: all 0.2s;
    border: 2px solid #dee2e6 !important;
}

.custom-card-radio .form-check-input:checked+.form-check-label {
    border-color: #0d6efd !important;
    background-color: #f8f9fa;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
