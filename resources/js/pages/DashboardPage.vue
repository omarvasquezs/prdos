<template>
  <AdminLayout>
    <div class="d-flex align-items-center justify-content-center" style="min-height: 70vh;">
      <div class="text-center">
        <div class="mb-4">
          <i class="fas fa-tools" style="font-size: 120px; color: #6c757d; opacity: 0.3;"></i>
        </div>
        
        <h1 class="display-3 fw-bold text-muted mb-3">En Construcción</h1>
        
        <p class="lead text-secondary mb-4">
          Estamos trabajando en esta sección del panel de administración.
        </p>
        
        <div class="d-flex justify-content-center gap-2 mb-4">
          <div class="spinner-grow text-primary" role="status" style="width: 0.8rem; height: 0.8rem;">
            <span class="visually-hidden">Loading...</span>
          </div>
          <div class="spinner-grow text-secondary" role="status" style="width: 0.8rem; height: 0.8rem;">
            <span class="visually-hidden">Loading...</span>
          </div>
          <div class="spinner-grow text-success" role="status" style="width: 0.8rem; height: 0.8rem;">
            <span class="visually-hidden">Loading...</span>
          </div>
        </div>
        
        <p class="text-muted">
          <i class="fas fa-clock me-2"></i>
          Pronto estará disponible
        </p>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/layouts/AdminLayout.vue';
</script>
