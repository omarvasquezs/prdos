<template>
  <div class="dashboard-home">
    <h2 class="mb-4">
      <i class="fas fa-chart-line me-2"></i>
      Dashboard
    </h2>
    
    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="stat-card stat-primary">
          <div class="stat-icon">
            <i class="fas fa-box"></i>
          </div>
          <div class="stat-content">
            <h3>{{ stats.productos }}</h3>
            <p>Productos</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card stat-success">
          <div class="stat-icon">
            <i class="fas fa-tags"></i>
          </div>
          <div class="stat-content">
            <h3>{{ stats.categorias }}</h3>
            <p>Categorías</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card stat-info">
          <div class="stat-icon">
            <i class="fas fa-users"></i>
          </div>
          <div class="stat-content">
            <h3>{{ stats.usuarios }}</h3>
            <p>Usuarios</p>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="stat-card stat-warning">
          <div class="stat-icon">
            <i class="fas fa-utensils"></i>
          </div>
          <div class="stat-content">
            <h3>{{ stats.pedidos }}</h3>
            <p>Pedidos Hoy</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Welcome Message -->
    <div class="card shadow-sm">
      <div class="card-body text-center py-5">
        <i class="fas fa-chart-pie fa-4x text-primary mb-3"></i>
        <h3>Bienvenido al Panel de Administración</h3>
        <p class="text-muted">
          Aquí podrás gestionar productos, categorías, usuarios y visualizar estadísticas del sistema.
        </p>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'DashboardHome',
  data() {
    return {
      stats: {
        productos: 0,
        categorias: 0,
        usuarios: 0,
        pedidos: 0
      }
    };
  },
  async mounted() {
    // Aquí puedes cargar estadísticas reales desde el backend
    // Por ahora, valores de ejemplo
    this.stats = {
      productos: 14,
      categorias: 4,
      usuarios: 3,
      pedidos: 0
    };
  }
};
</script>

<style scoped>
.dashboard-home {
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

.stat-card {
  background: white;
  border-radius: 12px;
  padding: 1.5rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
}

.stat-icon {
  width: 60px;
  height: 60px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}

.stat-primary .stat-icon {
  background: #e7f1ff;
  color: #667eea;
}

.stat-success .stat-icon {
  background: #d4edda;
  color: #28a745;
}

.stat-info .stat-icon {
  background: #d1ecf1;
  color: #17a2b8;
}

.stat-warning .stat-icon {
  background: #fff3cd;
  color: #ffc107;
}

.stat-content h3 {
  margin: 0;
  font-size: 2rem;
  font-weight: 700;
  color: #212529;
}

.stat-content p {
  margin: 0;
  color: #6c757d;
  font-size: 0.9rem;
}
</style>
