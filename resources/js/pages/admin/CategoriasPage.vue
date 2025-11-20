<template>
  <div class="categorias-page">
    <div class="card shadow-sm">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="fas fa-tags me-2"></i>
          Gestión de Categorías
        </h5>
        <button class="btn btn-primary" @click="abrirModalCategoria()">
          <i class="fas fa-plus me-2"></i>
          Nueva Categoría
        </button>
      </div>
      <div class="card-body">
        <!-- Filtros -->
        <div class="row mb-3">
          <div class="col-md-9">
            <input 
              type="text" 
              class="form-control" 
              placeholder="Buscar categorías..."
              v-model="searchCategorias"
              @input="buscarCategorias"
            >
          </div>
          <div class="col-md-3">
            <select class="form-select" v-model="paginationCategorias.per_page" @change="cambiarPerPageCategorias">
              <option :value="10">10 por página</option>
              <option :value="15">15 por página</option>
              <option :value="25">25 por página</option>
              <option :value="50">50 por página</option>
              <option :value="100">100 por página</option>
            </select>
          </div>
        </div>

        <!-- Tabla de categorías -->
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th class="text-center">Productos</th>
                <th class="text-center">Estado</th>
                <th class="text-center" style="width: 120px;">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="loading">
                <td colspan="5" class="text-center py-4">
                  <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Cargando...</span>
                  </div>
                </td>
              </tr>
              <tr v-else-if="categorias.length === 0">
                <td colspan="5" class="text-center py-4 text-muted">
                  <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                  No hay categorías registradas
                </td>
              </tr>
              <tr v-else v-for="categoria in categorias" :key="categoria.id">
                <td>
                  <strong>{{ categoria.name }}</strong>
                </td>
                <td>{{ categoria.description || '-' }}</td>
                <td class="text-center">
                  <span class="badge bg-info">{{ categoria.products_count || 0 }}</span>
                </td>
                <td class="text-center">
                  <span class="badge" :class="categoria.is_active ? 'bg-success' : 'bg-secondary'">
                    {{ categoria.is_active ? 'Activa' : 'Inactiva' }}
                  </span>
                </td>
                <td class="text-center">
                  <button 
                    class="btn btn-sm btn-outline-primary me-1" 
                    @click="abrirModalCategoria(categoria)"
                    title="Editar"
                  >
                    <i class="fas fa-edit"></i>
                  </button>
                  <button 
                    class="btn btn-sm btn-outline-danger" 
                    @click="eliminarCategoria(categoria)"
                    title="Eliminar"
                  >
                    <i class="fas fa-trash"></i>
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginación -->
        <div class="d-flex justify-content-between align-items-center mt-3">
          <div class="text-muted">
            Mostrando {{ paginationCategorias.from }} a {{ paginationCategorias.to }} de {{ paginationCategorias.total }} categorías
          </div>
          <nav v-if="paginationCategorias.last_page > 1">
            <ul class="pagination mb-0">
              <li class="page-item" :class="{ disabled: paginationCategorias.current_page === 1 }">
                <a class="page-link" href="#" @click.prevent="cambiarPaginaCategorias(paginationCategorias.current_page - 1)">
                  <i class="fas fa-chevron-left"></i>
                </a>
              </li>
              
              <template v-for="page in getVisiblePages()" :key="page">
                <li v-if="page === '...'" class="page-item disabled">
                  <span class="page-link">...</span>
                </li>
                <li v-else class="page-item" :class="{ active: paginationCategorias.current_page === page }">
                  <a class="page-link" href="#" @click.prevent="cambiarPaginaCategorias(page)">
                    {{ page }}
                  </a>
                </li>
              </template>

              <li class="page-item" :class="{ disabled: paginationCategorias.current_page === paginationCategorias.last_page }">
                <a class="page-link" href="#" @click.prevent="cambiarPaginaCategorias(paginationCategorias.current_page + 1)">
                  <i class="fas fa-chevron-right"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Modal de Categoría -->
    <div class="modal fade" ref="modalCategoria" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">
              <i class="fas fa-tags me-2"></i>
              {{ categoriaEditando ? 'Editar Categoría' : 'Nueva Categoría' }}
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form @submit.prevent="guardarCategoria">
              <div class="mb-3">
                <label class="form-label">Nombre <span class="text-danger">*</span></label>
                <input 
                  type="text" 
                  class="form-control" 
                  v-model="categoriaForm.name"
                  :class="{ 'is-invalid': erroresCategoria.name }"
                  required
                >
                <div class="invalid-feedback" v-if="erroresCategoria.name">
                  {{ erroresCategoria.name[0] }}
                </div>
              </div>

              <div class="mb-3">
                <label class="form-label">Descripción</label>
                <textarea 
                  class="form-control" 
                  v-model="categoriaForm.description"
                  :class="{ 'is-invalid': erroresCategoria.description }"
                  rows="3"
                ></textarea>
                <div class="invalid-feedback" v-if="erroresCategoria.description">
                  {{ erroresCategoria.description[0] }}
                </div>
              </div>

              <div class="mb-3">
                <div class="form-check form-switch">
                  <input 
                    class="form-check-input" 
                    type="checkbox" 
                    id="isActive"
                    v-model="categoriaForm.is_active"
                  >
                  <label class="form-check-label" for="isActive">
                    Categoría activa
                  </label>
                </div>
              </div>

              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                  Cancelar
                </button>
                <button type="submit" class="btn btn-primary" :disabled="guardandoCategoria">
                  <span v-if="guardandoCategoria">
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
  </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';
import { Modal } from 'bootstrap';

const categorias = ref([]);
const loading = ref(false);
const searchCategorias = ref('');
const categoriaEditando = ref(null);
const guardandoCategoria = ref(false);
const modalCategoria = ref(null);
let modalCategoriaInstance = null;

const paginationCategorias = reactive({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0
});

const categoriaForm = reactive({
  name: '',
  description: '',
  is_active: true
});

const erroresCategoria = ref({});

let searchTimeout = null;

onMounted(() => {
  cargarCategorias();
  modalCategoriaInstance = new Modal(modalCategoria.value);
});

const cargarCategorias = async () => {
  loading.value = true;
  try {
    const response = await axios.get('/api/admin/categorias', {
      params: {
        page: paginationCategorias.current_page,
        per_page: paginationCategorias.per_page,
        search: searchCategorias.value
      }
    });

    categorias.value = response.data.data;
    paginationCategorias.current_page = response.data.current_page;
    paginationCategorias.last_page = response.data.last_page;
    paginationCategorias.per_page = response.data.per_page;
    paginationCategorias.total = response.data.total;
    paginationCategorias.from = ((response.data.current_page - 1) * response.data.per_page) + 1;
    paginationCategorias.to = Math.min(paginationCategorias.from + response.data.per_page - 1, response.data.total);
  } catch (error) {
    console.error('Error al cargar categorías:', error);
    alert('Error al cargar las categorías');
  } finally {
    loading.value = false;
  }
};

const buscarCategorias = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    paginationCategorias.current_page = 1;
    cargarCategorias();
  }, 500);
};

const cambiarPaginaCategorias = (page) => {
  if (page >= 1 && page <= paginationCategorias.last_page) {
    paginationCategorias.current_page = page;
    cargarCategorias();
  }
};

const cambiarPerPageCategorias = () => {
  paginationCategorias.current_page = 1;
  cargarCategorias();
};

const getVisiblePages = () => {
  const pages = [];
  const current = paginationCategorias.current_page;
  const last = paginationCategorias.last_page;
  
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

const abrirModalCategoria = (categoria = null) => {
  categoriaEditando.value = categoria;
  erroresCategoria.value = {};
  
  if (categoria) {
    categoriaForm.name = categoria.name;
    categoriaForm.description = categoria.description || '';
    categoriaForm.is_active = categoria.is_active;
  } else {
    categoriaForm.name = '';
    categoriaForm.description = '';
    categoriaForm.is_active = true;
  }
  
  modalCategoriaInstance.show();
};

const guardarCategoria = async () => {
  guardandoCategoria.value = true;
  erroresCategoria.value = {};
  
  try {
    if (categoriaEditando.value) {
      await axios.put(`/api/admin/categorias/${categoriaEditando.value.id}`, categoriaForm);
    } else {
      await axios.post('/api/admin/categorias', categoriaForm);
    }
    
    modalCategoriaInstance.hide();
    cargarCategorias();
    alert(categoriaEditando.value ? 'Categoría actualizada exitosamente' : 'Categoría creada exitosamente');
  } catch (error) {
    if (error.response?.status === 422) {
      erroresCategoria.value = error.response.data.errors || {};
    } else {
      alert('Error al guardar la categoría');
    }
  } finally {
    guardandoCategoria.value = false;
  }
};

const eliminarCategoria = async (categoria) => {
  if (!confirm(`¿Estás seguro de eliminar la categoría "${categoria.name}"?`)) {
    return;
  }
  
  try {
    await axios.delete(`/api/admin/categorias/${categoria.id}`);
    cargarCategorias();
    alert('Categoría eliminada exitosamente');
  } catch (error) {
    alert('Error al eliminar la categoría');
  }
};
</script>

<style scoped>
.categorias-page {
  padding: 0;
}

.card {
  border: none;
  border-radius: 8px;
}

.card-header {
  border-bottom: 2px solid #dee2e6;
  padding: 1.25rem;
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
</style>
