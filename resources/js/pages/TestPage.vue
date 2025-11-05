<template>
  <AdminLayout>
    <div class="card shadow-sm">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="card-title mb-0">Página de Prueba</h2>
          <span class="badge bg-success">Activa</span>
        </div>

        <p class="text-muted mb-4">
          Esta es una página de prueba para verificar que el sistema de autenticación está funcionando correctamente.
        </p>

        <div class="alert alert-info border-start border-5 border-info">
          <div class="d-flex">
            <div class="flex-shrink-0 me-3">
              <svg class="bi" width="20" height="20" fill="currentColor">
                <use xlink:href="#info-circle"/>
              </svg>
            </div>
            <div>
              <p class="mb-0 small">
                Esta página solo es accesible para usuarios autenticados. Si no estuvieras logueado, habrías sido redirigido a la página de login.
              </p>
            </div>
          </div>
        </div>

        <div class="row g-4 mt-3">
          <div class="col-12 col-md-6">
            <div class="card h-100">
              <div class="card-body">
                <h3 class="h5 mb-3">Características Implementadas</h3>
                <ul class="list-unstyled">
                  <li class="mb-2">
                    <svg class="bi text-success me-2" width="16" height="16" fill="currentColor">
                      <use xlink:href="#check-circle"/>
                    </svg>
                    Sistema de autenticación
                  </li>
                  <li class="mb-2">
                    <svg class="bi text-success me-2" width="16" height="16" fill="currentColor">
                      <use xlink:href="#check-circle"/>
                    </svg>
                    Rutas protegidas con Vue Router
                  </li>
                  <li class="mb-2">
                    <svg class="bi text-success me-2" width="16" height="16" fill="currentColor">
                      <use xlink:href="#check-circle"/>
                    </svg>
                    Gestión de estado con Pinia
                  </li>
                  <li class="mb-2">
                    <svg class="bi text-success me-2" width="16" height="16" fill="currentColor">
                      <use xlink:href="#check-circle"/>
                    </svg>
                    Layouts separados (Login/Admin)
                  </li>
                  <li class="mb-2">
                    <svg class="bi text-success me-2" width="16" height="16" fill="currentColor">
                      <use xlink:href="#check-circle"/>
                    </svg>
                    Integración Laravel + Vue 3
                  </li>
                  <li class="mb-2">
                    <svg class="bi text-success me-2" width="16" height="16" fill="currentColor">
                      <use xlink:href="#check-circle"/>
                    </svg>
                    Bootstrap 5
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-6">
            <div class="card h-100">
              <div class="card-body">
                <h3 class="h5 mb-3">Información del Usuario</h3>
                <div class="mb-3">
                  <p class="text-muted small mb-1">Email</p>
                  <p class="fw-medium">{{ authStore.user?.email }}</p>
                </div>
                <div class="mb-3">
                  <p class="text-muted small mb-1">Nombre</p>
                  <p class="fw-medium">{{ authStore.user?.name || 'No especificado' }}</p>
                </div>
                <div>
                  <p class="text-muted small mb-1">Estado</p>
                  <span class="badge bg-success">Autenticado</span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="alert alert-warning mt-4">
          <h5 class="alert-heading">Nota de Desarrollo</h5>
          <p class="mb-0 small">
            Esta es una página de prueba. Puedes modificarla o crear nuevas páginas siguiendo la misma estructura.
            Todas las rutas en el router con <code>meta: { requiresAuth: true }</code> 
            estarán protegidas automáticamente.
          </p>
        </div>
      </div>
    </div>

    <!-- SVG Icons -->
    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
      <symbol id="check-circle" viewBox="0 0 16 16">
        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
        <path d="M10.97 4.97a.235.235 0 0 0-.02.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-1.071-1.05z"/>
      </symbol>
      <symbol id="info-circle" viewBox="0 0 16 16">
        <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
        <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
      </symbol>
    </svg>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/layouts/AdminLayout.vue';
import { useAuthStore } from '@/stores/auth';

const authStore = useAuthStore();
</script>
