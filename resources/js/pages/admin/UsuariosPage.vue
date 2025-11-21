<template>
  <div class="usuarios-page">
    <div class="card shadow-sm">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="fas fa-users me-2"></i>
          Gestión de Usuarios
        </h5>
        <button class="btn btn-primary" @click="abrirModalUsuario()">
          <i class="fas fa-plus me-2"></i>
          Nuevo Usuario
        </button>
      </div>
      <div class="card-body">
        <!-- Filtros -->
        <div class="row mb-3">
          <div class="col-md-9">
            <input 
              type="text" 
              class="form-control" 
              placeholder="Buscar usuarios..."
              v-model="searchUsuarios"
              @input="buscarUsuarios"
            >
          </div>
          <div class="col-md-3">
            <select class="form-select" v-model="paginationUsuarios.per_page" @change="cambiarPerPageUsuarios">
              <option :value="10">10 por página</option>
              <option :value="15">15 por página</option>
              <option :value="25">25 por página</option>
              <option :value="50">50 por página</option>
              <option :value="100">100 por página</option>
            </select>
          </div>
        </div>

        <!-- Tabla de usuarios -->
        <div class="table-container">
          <div class="table-wrapper">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Nombre</th>
                  <th>Usuario</th>
                  <th>Email</th>
                  <th>Roles</th>
                  <th class="text-center" style="width: 120px;">Acciones</th>
                </tr>
              </thead>
            </table>
          </div>
          <div class="table-body-wrapper">
            <table class="table table-hover align-middle mb-0">
              <tbody>
                <tr v-if="loading">
                  <td colspan="5" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Cargando...</span>
                    </div>
                  </td>
                </tr>
                <tr v-else-if="usuarios.length === 0">
                  <td colspan="5" class="text-center py-4 text-muted">
                    <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                    No hay usuarios registrados
                  </td>
                </tr>
                <tr v-else v-for="usuario in usuarios" :key="usuario.id">
                  <td>
                    <strong>{{ usuario.name }}</strong>
                  </td>
                  <td>{{ usuario.username }}</td>
                  <td>{{ usuario.email }}</td>
                  <td>
                    <span 
                      v-for="role in usuario.roles" 
                      :key="role.id" 
                      class="badge bg-primary me-1"
                    >
                      {{ role.name }}
                    </span>
                  </td>
                  <td class="text-center">
                    <button 
                      class="btn btn-sm btn-outline-primary me-1" 
                      @click="abrirModalUsuario(usuario)"
                      title="Editar"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button 
                      class="btn btn-sm btn-outline-warning me-1" 
                      @click="abrirModalPassword(usuario)"
                      title="Cambiar contraseña"
                    >
                      <i class="fas fa-key"></i>
                    </button>
                    <button 
                      class="btn btn-sm btn-outline-danger" 
                      @click="eliminarUsuario(usuario)"
                      title="Eliminar"
                    >
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Paginación -->
        <div class="d-flex justify-content-between align-items-center mt-3">
          <div class="text-muted">
            Mostrando {{ paginationUsuarios.from }} a {{ paginationUsuarios.to }} de {{ paginationUsuarios.total }} usuarios
          </div>
          <nav v-if="paginationUsuarios.last_page > 1">
            <ul class="pagination mb-0">
              <li class="page-item" :class="{ disabled: paginationUsuarios.current_page === 1 }">
                <a class="page-link" href="#" @click.prevent="cambiarPaginaUsuarios(paginationUsuarios.current_page - 1)">
                  <i class="fas fa-chevron-left"></i>
                </a>
              </li>
              
              <template v-for="page in getVisiblePages()" :key="page">
                <li v-if="page === '...'" class="page-item disabled">
                  <span class="page-link">...</span>
                </li>
                <li v-else class="page-item" :class="{ active: paginationUsuarios.current_page === page }">
                  <a class="page-link" href="#" @click.prevent="cambiarPaginaUsuarios(page)">
                    {{ page }}
                  </a>
                </li>
              </template>

              <li class="page-item" :class="{ disabled: paginationUsuarios.current_page === paginationUsuarios.last_page }">
                <a class="page-link" href="#" @click.prevent="cambiarPaginaUsuarios(paginationUsuarios.current_page + 1)">
                  <i class="fas fa-chevron-right"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Modal de Usuario -->
    <div class="modal fade" ref="modalUsuario" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-user me-2"></i>
              {{ usuarioEditando ? 'Editar Usuario' : 'Nuevo Usuario' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="guardarUsuario">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label class="form-label">Nombre <span class="text-danger">*</span></label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="usuarioForm.name"
                    :class="{ 'is-invalid': erroresUsuario.name }"
                    required
                  >
                  <div class="invalid-feedback" v-if="erroresUsuario.name">
                    {{ erroresUsuario.name[0] }}
                  </div>
                </div>

                <div class="col-md-6 mb-3">
                  <label class="form-label">Usuario <span class="text-danger">*</span></label>
                  <input 
                    type="text" 
                    class="form-control" 
                    v-model="usuarioForm.username"
                    :class="{ 'is-invalid': erroresUsuario.username }"
                    required
                  >
                  <div class="invalid-feedback" v-if="erroresUsuario.username">
                    {{ erroresUsuario.username[0] }}
                  </div>
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Email</label>
                <input 
                  type="email" 
                  class="form-control" 
                  v-model="usuarioForm.email"
                  :class="{ 'is-invalid': erroresUsuario.email }"
                >
                <div class="invalid-feedback" v-if="erroresUsuario.email">
                  {{ erroresUsuario.email[0] }}
                </div>
              </div>

              <div class="mb-3" v-if="!usuarioEditando">
                <label class="form-label">
                  Contraseña 
                  <span class="text-danger">*</span>
                </label>
                <input 
                  type="password" 
                  class="form-control" 
                  v-model="usuarioForm.password"
                  :class="{ 'is-invalid': erroresUsuario.password }"
                  required
                >
                <div class="invalid-feedback" v-if="erroresUsuario.password">
                  {{ erroresUsuario.password[0] }}
                </div>
              </div>

              <div class="mb-3" v-if="!usuarioEditando">
                <label class="form-label">
                  Confirmar Contraseña 
                  <span class="text-danger">*</span>
                </label>
                <input 
                  type="password" 
                  class="form-control" 
                  v-model="usuarioForm.password_confirmation"
                  :class="{ 'is-invalid': erroresUsuario.password_confirmation }"
                  required
                >
                <div class="invalid-feedback" v-if="erroresUsuario.password_confirmation">
                  {{ erroresUsuario.password_confirmation[0] }}
                </div>
              </div>

              <!-- Selector de Roles estilo Drupal -->
              <div class="mb-3">
                <label class="form-label">Roles <span class="text-danger">*</span></label>
                
                <!-- Tags de roles seleccionados -->
                <div class="roles-tags mb-2" v-if="selectedRoles.length > 0">
                  <span 
                    v-for="role in selectedRoles" 
                    :key="role.id" 
                    class="role-tag"
                  >
                    {{ role.name }}
                    <button 
                      type="button" 
                      class="btn-remove-role" 
                      @click="removeRole(role.id)"
                      title="Remover rol"
                    >
                      ×
                    </button>
                  </span>
                </div>

                <!-- Input de búsqueda -->
                <div class="role-search-container" ref="roleSearchContainer">
                  <input 
                    type="text" 
                    class="form-control" 
                    :class="{ 'is-invalid': erroresUsuario.roles }"
                    placeholder="Buscar roles..."
                    v-model="roleSearchQuery"
                    @focus="showRoleDropdown = true"
                    @input="filterRoles"
                  >
                  
                  <!-- Dropdown de roles -->
                  <div 
                    class="roles-dropdown" 
                    v-if="showRoleDropdown && filteredRoles.length > 0"
                  >
                    <div 
                      v-for="role in filteredRoles" 
                      :key="role.id"
                      class="role-option"
                      @click="addRole(role)"
                    >
                      {{ role.name }}
                    </div>
                  </div>
                </div>

                <div class="invalid-feedback d-block" v-if="erroresUsuario.roles">
                  {{ erroresUsuario.roles[0] }}
                </div>
                <small class="text-muted">Busca y selecciona los roles del usuario</small>
              </div>

              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                  Cancelar
                </button>
                <button type="submit" class="btn btn-primary" :disabled="guardandoUsuario">
                  <span v-if="guardandoUsuario">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Guardando...
                  </span>
                  <span v-else>
                    <i class="fas fa-save me-2"></i>
                    Guardar
                  </span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Cambiar Contraseña -->
    <div class="modal fade" ref="modalPassword" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-key me-2"></i>
              Cambiar Contraseña
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="cambiarPassword">
              <div class="mb-3">
                <p class="text-muted">Usuario: <strong>{{ usuarioPassword?.name }}</strong></p>
              </div>

              <div class="mb-3">
                <label class="form-label">Acción</label>
                <select class="form-select" v-model="passwordForm.action" required>
                  <option value="">Seleccionar acción...</option>
                  <option value="new">Establecer nueva contraseña</option>
                  <option value="reset">Restablecer a 12345678</option>
                </select>
              </div>

              <div v-if="passwordForm.action === 'new'">
                <div class="mb-3">
                  <label class="form-label">Nueva Contraseña <span class="text-danger">*</span></label>
                  <input 
                    type="password" 
                    class="form-control" 
                    v-model="passwordForm.new_password"
                    :class="{ 'is-invalid': erroresPassword.new_password }"
                    minlength="6"
                    required
                  >
                  <div class="invalid-feedback" v-if="erroresPassword.new_password">
                    {{ erroresPassword.new_password[0] }}
                  </div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Confirmar Contraseña <span class="text-danger">*</span></label>
                  <input 
                    type="password" 
                    class="form-control" 
                    v-model="passwordForm.new_password_confirmation"
                    :class="{ 'is-invalid': erroresPassword.new_password_confirmation }"
                    minlength="6"
                    required
                  >
                  <div class="invalid-feedback" v-if="erroresPassword.new_password_confirmation">
                    {{ erroresPassword.new_password_confirmation[0] }}
                  </div>
                </div>
              </div>

              <div v-if="passwordForm.action === 'reset'" class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                La contraseña se restablecerá a: <strong>12345678</strong>
              </div>

              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                  Cancelar
                </button>
                <button type="submit" class="btn btn-primary" :disabled="guardandoPassword">
                  <span v-if="guardandoPassword">
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Procesando...
                  </span>
                  <span v-else>
                    <i class="fas fa-save me-2"></i>
                    Guardar
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

<script setup>
import { ref, onMounted, reactive, computed } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

const usuarios = ref([]);
const loading = ref(false);
const searchUsuarios = ref('');
const usuarioEditando = ref(null);
const guardandoUsuario = ref(false);
const modalUsuario = ref(null);
let modalUsuarioInstance = null;

// Password modal
const usuarioPassword = ref(null);
const guardandoPassword = ref(false);
const modalPassword = ref(null);
let modalPasswordInstance = null;

// Roles
const allRoles = ref([]);
const selectedRoles = ref([]);
const roleSearchQuery = ref('');
const showRoleDropdown = ref(false);
const roleSearchContainer = ref(null);

const paginationUsuarios = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0
});

const usuarioForm = reactive({
  name: '',
  username: '',
  email: '',
  password: '',
  password_confirmation: '',
  roles: []
});

const passwordForm = reactive({
  action: '',
  new_password: '',
  new_password_confirmation: ''
});

const erroresUsuario = ref({});
const erroresPassword = ref({});

let searchTimeout = null;

// Computed para filtrar roles
const filteredRoles = computed(() => {
  const query = roleSearchQuery.value.toLowerCase();
  const selectedIds = selectedRoles.value.map(r => r.id);
  
  return allRoles.value.filter(role => 
    !selectedIds.includes(role.id) && 
    role.name.toLowerCase().includes(query)
  );
});

onMounted(() => {
  cargarUsuarios();
  cargarRoles();
  modalUsuarioInstance = new Modal(modalUsuario.value);
  modalPasswordInstance = new Modal(modalPassword.value);
  
  // Click fuera del dropdown para cerrarlo
  document.addEventListener('click', handleClickOutside);
});

const handleClickOutside = (event) => {
  if (roleSearchContainer.value && !roleSearchContainer.value.contains(event.target)) {
    showRoleDropdown.value = false;
  }
};

const cargarRoles = async () => {
  try {
    const response = await axios.get('/api/admin/usuarios/roles');
    allRoles.value = response.data;
  } catch (error) {
    console.error('Error al cargar roles:', error);
  }
};

const cargarUsuarios = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/admin/usuarios', {
      params: {
        page: paginationUsuarios.current_page,
        per_page: paginationUsuarios.per_page,
        search: searchUsuarios.value
      }
    });

    usuarios.value = response.data.data;
    paginationUsuarios.current_page = response.data.current_page;
    paginationUsuarios.last_page = response.data.last_page;
    paginationUsuarios.per_page = response.data.per_page;
    paginationUsuarios.total = response.data.total;
    paginationUsuarios.from = ((response.data.current_page - 1) * response.data.per_page) + 1;
    paginationUsuarios.to = Math.min(paginationUsuarios.from + response.data.per_page - 1, response.data.total);
  } catch (error) {
    console.error('Error al cargar usuarios:', error);
    alert('Error al cargar los usuarios');
  } finally {
    loading.value = false;
  }
};

const buscarUsuarios = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    paginationUsuarios.current_page = 1;
    cargarUsuarios();
  }, 500);
};

const cambiarPaginaUsuarios = (page) => {
  if (page >= 1 && page <= paginationUsuarios.last_page) {
    paginationUsuarios.current_page = page;
    cargarUsuarios();
  }
};

const cambiarPerPageUsuarios = () => {
  paginationUsuarios.current_page = 1;
  cargarUsuarios();
};

const getVisiblePages = () => {
  const pages = [];
  const current = paginationUsuarios.current_page;
  const last = paginationUsuarios.last_page;
  
  if (last <= 7) {
    for (let i = 1; i <= last; i++) {
      pages.push(i);
    }
  } else {
    if (current <= 4) {
      for (let i = 1; i <= 5; i++) pages.push(i);
      pages.push('...');
      pages.push(last);
    } else if (current >= last - 3) {
      pages.push(1);
      pages.push('...');
      for (let i = last - 4; i <= last; i++) pages.push(i);
    } else {
      pages.push(1);
      pages.push('...');
      for (let i = current - 1; i <= current + 1; i++) pages.push(i);
      pages.push('...');
      pages.push(last);
    }
  }
  
  return pages;
};

const addRole = (role) => {
  if (!selectedRoles.value.find(r => r.id === role.id)) {
    selectedRoles.value.push(role);
    roleSearchQuery.value = '';
  }
};

const removeRole = (roleId) => {
  selectedRoles.value = selectedRoles.value.filter(r => r.id !== roleId);
};

const filterRoles = () => {
  showRoleDropdown.value = true;
};

const abrirModalUsuario = (usuario = null) => {
  usuarioEditando.value = usuario;
  erroresUsuario.value = {};
  selectedRoles.value = [];
  roleSearchQuery.value = '';
  
  if (usuario) {
    usuarioForm.name = usuario.name;
    usuarioForm.username = usuario.username;
    usuarioForm.email = usuario.email || '';
    usuarioForm.password = '';
    usuarioForm.password_confirmation = '';
    selectedRoles.value = [...usuario.roles];
  } else {
    usuarioForm.name = '';
    usuarioForm.username = '';
    usuarioForm.email = '';
    usuarioForm.password = '';
    usuarioForm.password_confirmation = '';
  }
  
  modalUsuarioInstance.show();
};

const guardarUsuario = async () => {
  guardandoUsuario.value = true;
  erroresUsuario.value = {};
  
  // Preparar datos
  const data = {
    name: usuarioForm.name,
    username: usuarioForm.username,
    email: usuarioForm.email || null,
    roles: selectedRoles.value.map(r => r.id)
  };
  
  // Solo agregar password en creación
  if (!usuarioEditando.value) {
    data.password = usuarioForm.password;
    data.password_confirmation = usuarioForm.password_confirmation;
  }
  
  try {
    if (usuarioEditando.value) {
      await axios.put(`/api/admin/usuarios/${usuarioEditando.value.id}`, data);
    } else {
      await axios.post('/api/admin/usuarios', data);
    }
    
    modalUsuarioInstance.hide();
    cargarUsuarios();
    alert(usuarioEditando.value ? 'Usuario actualizado exitosamente' : 'Usuario creado exitosamente');
  } catch (error) {
    if (error.response?.status === 422) {
      erroresUsuario.value = error.response.data.errors || {};
    } else {
      alert('Error al guardar el usuario');
    }
  } finally {
    guardandoUsuario.value = false;
  }
};

const eliminarUsuario = async (usuario) => {
  if (!confirm(`¿Estás seguro de eliminar el usuario "${usuario.name}"?`)) {
    return;
  }
  
  try {
    await axios.delete(`/api/admin/usuarios/${usuario.id}`);
    cargarUsuarios();
    alert('Usuario eliminado exitosamente');
  } catch (error) {
    const message = error.response?.data?.error || 'Error al eliminar el usuario';
    alert(message);
  }
};

const abrirModalPassword = (usuario) => {
  usuarioPassword.value = usuario;
  erroresPassword.value = {};
  passwordForm.action = '';
  passwordForm.new_password = '';
  passwordForm.new_password_confirmation = '';
  modalPasswordInstance.show();
};

const cambiarPassword = async () => {
  guardandoPassword.value = true;
  erroresPassword.value = {};
  
  try {
    const data = {
      action: passwordForm.action
    };
    
    if (passwordForm.action === 'new') {
      data.new_password = passwordForm.new_password;
      data.new_password_confirmation = passwordForm.new_password_confirmation;
    }
    
    await axios.post(`/api/admin/usuarios/${usuarioPassword.value.id}/change-password`, data);
    
    modalPasswordInstance.hide();
    alert(passwordForm.action === 'reset' 
      ? 'Contraseña restablecida a 12345678' 
      : 'Contraseña actualizada exitosamente'
    );
  } catch (error) {
    if (error.response?.status === 422) {
      erroresPassword.value = error.response.data.errors || {};
    } else {
      alert('Error al cambiar la contraseña');
    }
  } finally {
    guardandoPassword.value = false;
  }
};
</script>

<style scoped>
.usuarios-page {
  padding: 1rem;
}

.card-header {
  border-bottom: 2px solid #dee2e6;
  padding: 0.5rem 1.25rem;
}

.table th {
  font-weight: 600;
  color: #495057;
  border-bottom: 2px solid #dee2e6;
}

.pagination {
  margin-bottom: 0;
}

.page-link {
  color: #495057;
  border-color: #dee2e6;
}

.page-item.active .page-link {
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.badge {
  font-weight: 500;
  padding: 0.35em 0.65em;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

/* Estilos para el selector de roles estilo Drupal */
.roles-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  padding: 0.5rem;
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
  border-radius: 4px;
}

.role-tag {
  display: inline-flex;
  align-items: center;
  padding: 0.35rem 0.65rem;
  background-color: #0d6efd;
  color: white;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 500;
}

.btn-remove-role {
  background: none;
  border: none;
  color: white;
  font-size: 1.25rem;
  line-height: 1;
  padding: 0;
  margin-left: 0.5rem;
  cursor: pointer;
  opacity: 0.8;
  transition: opacity 0.2s;
}

.btn-remove-role:hover {
  opacity: 1;
}

.role-search-container {
  position: relative;
}

.roles-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  right: 0;
  max-height: 200px;
  overflow-y: auto;
  background: white;
  border: 1px solid #dee2e6;
  border-top: none;
  border-radius: 0 0 4px 4px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  z-index: 1000;
}

.role-option {
  padding: 0.75rem 1rem;
  cursor: pointer;
  transition: background-color 0.2s;
}

.role-option:hover {
  background-color: #f8f9fa;
}

.role-option:active {
  background-color: #e9ecef;
}

/* Fixed Table Styles */
.table-container {
  border: 1px solid #dee2e6;
  border-radius: 4px;
  overflow: hidden;
  box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.table-wrapper {
  background: #f8f9fa;
}

.table-wrapper table {
  margin-bottom: 0;
}

.table-wrapper thead th {
  border-bottom: 2px solid #dee2e6;
  font-weight: 600;
  position: sticky;
  top: 0;
  background: #f8f9fa;
  z-index: 10;
}

.table-body-wrapper {
  height: calc(100vh - 380px);
  overflow-y: auto;
  overflow-x: hidden;
}

.table-body-wrapper table {
  margin-bottom: 0;
}

.table-body-wrapper thead {
  visibility: collapse;
}

/* Asegurar que las columnas tengan el mismo ancho en header y body */
.table-wrapper table,
.table-body-wrapper table {
  table-layout: fixed;
  width: 100%;
}

.table-wrapper th:nth-child(1),
.table-body-wrapper td:nth-child(1) {
  width: 20%;
}

.table-wrapper th:nth-child(2),
.table-body-wrapper td:nth-child(2) {
  width: 18%;
}

.table-wrapper th:nth-child(3),
.table-body-wrapper td:nth-child(3) {
  width: 25%;
}

.table-wrapper th:nth-child(4),
.table-body-wrapper td:nth-child(4) {
  width: 22%;
}

.table-wrapper th:nth-child(5),
.table-body-wrapper td:nth-child(5) {
  width: 15%;
}

/* Scroll personalizado */
.table-body-wrapper::-webkit-scrollbar {
  width: 8px;
}

.table-body-wrapper::-webkit-scrollbar-track {
  background: #f1f1f1;
}

.table-body-wrapper::-webkit-scrollbar-thumb {
  background: #888;
  border-radius: 4px;
}

.table-body-wrapper::-webkit-scrollbar-thumb:hover {
  background: #555;
}
</style>
