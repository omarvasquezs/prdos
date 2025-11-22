<template>
    <AdminLayout>
        <div class="dashboard-container">
            <!-- Fixed Sidebar -->
            <div class="dashboard-sidebar">
                <div class="sidebar-content">
                    <nav class="sidebar-nav">
                        <router-link to="/contador/movimientos" class="nav-item" active-class="active">
                            <i class="fas fa-list-alt"></i>
                            <span>Movimientos</span>
                        </router-link>
                        <router-link to="/contador/exportar" class="nav-item" active-class="active">
                            <i class="fas fa-file-export"></i>
                            <span>Exportar</span>
                        </router-link>
                    </nav>
                </div>
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
    name: 'ContadorPage',
    components: {
        AdminLayout
    }
}
</script>

<style scoped>
.dashboard-container {
    display: flex;
    padding-top: 60px;
    /* Height of TopBar */
    min-height: 100vh;
}

.dashboard-sidebar {
    position: fixed;
    left: 0;
    top: 60px;
    width: 250px;
    height: calc(100vh - 60px);
    background: white;
    border-right: 1px solid #dee2e6;
    overflow-y: auto;
    z-index: 100;
}

.sidebar-content {
    padding: 1rem 0;
}

.sidebar-nav {
    display: flex;
    flex-direction: column;
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
    color: #6c757d;
}

.nav-item span {
    font-weight: 500;
}

.nav-item:hover {
    background: #f8f9fa;
    color: #667eea;
}

.nav-item:hover i {
    color: #667eea;
}

.nav-item.active {
    background: #e7f1ff;
    color: #667eea;
    border-left-color: #667eea;
    font-weight: 600;
}

.nav-item.active i {
    color: #667eea;
}

.dashboard-content {
    margin-left: 250px;
    /* Width of sidebar */
    flex: 1;
    padding: 2rem;
    min-height: calc(100vh - 60px);
}
</style>
