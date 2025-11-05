<template>
  <AdminLayout>
    <div class="card shadow-sm">
      <div class="card-body">
        <h2 class="card-title mb-4">Bienvenido al Dashboard</h2>
        
        <div class="row g-4 mt-3">
          <!-- Card 1 -->
          <div class="col-12 col-md-4">
            <div class="card text-white bg-primary">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <p class="card-text opacity-75">Usuarios</p>
                    <h3 class="card-title display-5">1,234</h3>
                  </div>
                  <div class="bg-white bg-opacity-25 rounded-circle p-3">
                    <svg class="bi" width="32" height="32" fill="currentColor">
                      <use xlink:href="#people"/>
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 2 -->
          <div class="col-12 col-md-4">
            <div class="card text-white bg-success">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <p class="card-text opacity-75">Ingresos</p>
                    <h3 class="card-title display-5">$45,231</h3>
                  </div>
                  <div class="bg-white bg-opacity-25 rounded-circle p-3">
                    <svg class="bi" width="32" height="32" fill="currentColor">
                      <use xlink:href="#currency-dollar"/>
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 3 -->
          <div class="col-12 col-md-4">
            <div class="card text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                  <div>
                    <p class="card-text opacity-75">Proyectos</p>
                    <h3 class="card-title display-5">42</h3>
                  </div>
                  <div class="bg-white bg-opacity-25 rounded-circle p-3">
                    <svg class="bi" width="32" height="32" fill="currentColor">
                      <use xlink:href="#folder"/>
                    </svg>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="mt-5">
          <h3 class="h5 mb-3">Actividad Reciente</h3>
          <div class="alert alert-light border">
            <p class="mb-2">Esta es tu página principal de administración.</p>
            <p class="mb-0">Aquí puedes ver estadísticas y gestionar tu aplicación.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- SVG Icons -->
    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
      <symbol id="people" viewBox="0 0 16 16">
        <path d="M15 14s1 0 1-1-1-4-5-4-5 3-5 4 1 1 1 1h8zm-7.978-1A.261.261 0 0 1 7 12.996c.001-.264.167-1.03.76-1.72C8.312 10.629 9.282 10 11 10c1.717 0 2.687.63 3.24 1.276.593.69.758 1.457.76 1.72l-.008.002a.274.274 0 0 1-.014.002H7.022zM11 7a2 2 0 1 0 0-4 2 2 0 0 0 0 4zm3-2a3 3 0 1 1-6 0 3 3 0 0 1 6 0zM6.936 9.28a5.88 5.88 0 0 0-1.23-.247A7.35 7.35 0 0 0 5 9c-4 0-5 3-5 4 0 .667.333 1 1 1h4.216A2.238 2.238 0 0 1 5 13c0-1.01.377-2.042 1.09-2.904.243-.294.526-.569.846-.816zM4.92 10A5.493 5.493 0 0 0 4 13H1c0-.26.164-1.03.76-1.724.545-.636 1.492-1.256 3.16-1.275zM1.5 5.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0zm3-2a2 2 0 1 0 0 4 2 2 0 0 0 0-4z"/>
      </symbol>
      <symbol id="currency-dollar" viewBox="0 0 16 16">
        <path d="M4 10.781c.148 1.667 1.513 2.85 3.591 3.003V15h1.043v-1.216c2.27-.179 3.678-1.438 3.678-3.3 0-1.59-.947-2.51-2.956-3.028l-.722-.187V3.467c1.122.11 1.879.714 2.07 1.616h1.47c-.166-1.6-1.54-2.748-3.54-2.875V1H7.591v1.233c-1.939.23-3.27 1.472-3.27 3.156 0 1.454.966 2.483 2.661 2.917l.61.162v4.031c-1.149-.17-1.94-.8-2.131-1.718H4zm3.391-3.836c-1.043-.263-1.6-.825-1.6-1.616 0-.944.704-1.641 1.8-1.828v3.495l-.2-.05zm1.591 1.872c1.287.323 1.852.859 1.852 1.769 0 1.097-.826 1.828-2.2 1.939V8.73l.348.086z"/>
      </symbol>
      <symbol id="folder" viewBox="0 0 16 16">
        <path d="M1 3.5A1.5 1.5 0 0 1 2.5 2h2.764c.958 0 1.76.56 2.311 1.184C7.985 3.648 8.48 4 9 4h4.5A1.5 1.5 0 0 1 15 5.5v7a1.5 1.5 0 0 1-1.5 1.5h-11A1.5 1.5 0 0 1 1 12.5v-9zM2.5 3a.5.5 0 0 0-.5.5V6h12v-.5a.5.5 0 0 0-.5-.5H9c-.964 0-1.71-.629-2.174-1.154C6.374 3.334 5.82 3 5.264 3H2.5zM14 7H2v5.5a.5.5 0 0 0 .5.5h11a.5.5 0 0 0 .5-.5V7z"/>
      </symbol>
    </svg>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from '@/layouts/AdminLayout.vue';
</script>
