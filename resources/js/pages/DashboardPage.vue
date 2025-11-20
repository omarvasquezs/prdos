<template>
  <AdminLayout>
    <div class="dashboard-container">
      <!-- Sidebar -->
      <div class="dashboard-sidebar">
        <div class="sidebar-header">
          <h5><i class="fas fa-cogs me-2"></i>Administración</h5>
        </div>
        <nav class="sidebar-nav">
          <router-link 
            to="/dashboard/productos" 
            class="nav-item"
            active-class="active"
          >
            <i class="fas fa-box"></i>
            <span>Productos</span>
          </router-link>
          <router-link 
            to="/dashboard/categorias" 
            class="nav-item"
            active-class="active"
          >
            <i class="fas fa-tags"></i>
            <span>Categorías</span>
          </router-link>
          <router-link 
            to="/dashboard/usuarios" 
            class="nav-item"
            active-class="active"
          >
            <i class="fas fa-users"></i>
            <span>Usuarios</span>
          </router-link>
        </nav>
      </div>

      <!-- Main Content -->
      <div class="dashboard-content">
        <router-view></router-view>
      </div>
    </div>
  </AdminLayout>
</template>

<script>
import AdminLayout from '@/layouts/AdminLayout.vue';

export default {
  name: 'DashboardPage',
  components: {
    AdminLayout
  }
}
</script>

<style scoped>
.dashboard-container {
  display: flex;
  min-height: 100vh;
  background: #f8f9fa;
}

.dashboard-sidebar {
  width: 250px;
  background: white;
  border-right: 1px solid #dee2e6;
  padding: 0;
}

.sidebar-header {
  padding: 1.5rem 1rem;
  border-bottom: 1px solid #dee2e6;
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  color: white;
}

.sidebar-header h5 {
  margin: 0;
  font-size: 1.1rem;
  font-weight: 600;
}

.sidebar-nav {
  padding: 1rem 0;
}

.nav-item {
  display: flex;
  align-items: center;
  padding: 0.875rem 1.5rem;
  color: #495057;
  text-decoration: none;
  transition: all 0.2s;
  border-left: 3px solid transparent;
}

.nav-item i {
  font-size: 1.1rem;
  width: 24px;
  margin-right: 0.75rem;
}

.nav-item span {
  font-weight: 500;
}

.nav-item:hover {
  background: #f8f9fa;
  color: #667eea;
}

.nav-item.active {
  background: #e7f1ff;
  color: #667eea;
  border-left-color: #667eea;
  font-weight: 600;
}

.dashboard-content {
  flex: 1;
  padding: 2rem;
  overflow-y: auto;
}
</style>
