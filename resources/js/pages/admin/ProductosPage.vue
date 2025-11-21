<template>
  <div class="productos-page">
    <div class="card shadow-sm">
      <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
          <i class="fas fa-box me-2"></i>
          Gestión de Productos
        </h5>
        <button class="btn btn-primary" @click="abrirModalProducto()">
          <i class="fas fa-plus me-2"></i>
          Nuevo Producto
        </button>
      </div>
      <div class="card-body">
        <!-- Filtros -->
        <div class="row mb-3">
          <div class="col-md-5">
            <input 
              type="text" 
              class="form-control" 
              placeholder="Buscar productos..."
              v-model="searchProductos"
              @input="buscarProductos"
            >
          </div>
          <div class="col-md-4">
            <select class="form-select" v-model="filterCategoria" @change="cargarProductos">
              <option value="">Todas las categorías</option>
              <option v-for="cat in categoriasActivas" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>
          <div class="col-md-3">
            <select class="form-select" v-model="paginationProductos.per_page" @change="cambiarPerPageProductos">
              <option :value="10">10 por página</option>
              <option :value="15">15 por página</option>
              <option :value="25">25 por página</option>
              <option :value="50">50 por página</option>
              <option :value="100">100 por página</option>
            </select>
          </div>
        </div>

        <!-- Tabla de productos -->
        <div class="table-container">
          <div class="table-wrapper">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Nombre</th>
                  <th>Categoría</th>
                  <th>Precio</th>
                  <th>Estado</th>
                  <th class="text-end">Acciones</th>
                </tr>
              </thead>
            </table>
          </div>
          <div class="table-body-wrapper">
            <table class="table table-hover align-middle mb-0">
              <tbody>
                <tr v-if="loadingProductos">
                  <td colspan="5" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                      <span class="visually-hidden">Cargando...</span>
                    </div>
                  </td>
                </tr>
                <tr v-else-if="productos.length === 0">
                  <td colspan="5" class="text-center text-muted py-4">
                    No hay productos registrados
                  </td>
                </tr>
                <tr v-else v-for="producto in productos" :key="producto.id">
                  <td>
                    <strong>{{ producto.name }}</strong>
                    <br>
                    <small class="text-muted">{{ producto.description }}</small>
                  </td>
                  <td>
                    <span class="badge bg-info">{{ producto.category.name }}</span>
                  </td>
                  <td>
                    <strong>{{ producto.price_formatted }}</strong>
                  </td>
                  <td>
                    <span class="badge" :class="producto.is_available ? 'bg-success' : 'bg-secondary'">
                      {{ producto.is_available ? 'Disponible' : 'No disponible' }}
                    </span>
                  </td>
                  <td class="text-end">
                    <button 
                      class="btn btn-sm btn-outline-primary me-1"
                      @click="abrirModalProducto(producto)"
                      title="Editar"
                    >
                      <i class="fas fa-edit"></i>
                    </button>
                    <button 
                      class="btn btn-sm btn-outline-danger"
                      @click="eliminarProducto(producto)"
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
        <div v-if="paginationProductos.last_page > 1" class="d-flex justify-content-between align-items-center mt-3">
          <div class="text-muted">
            Mostrando {{ ((paginationProductos.current_page - 1) * paginationProductos.per_page) + 1 }} 
            a {{ Math.min(paginationProductos.current_page * paginationProductos.per_page, paginationProductos.total) }} 
            de {{ paginationProductos.total }} productos
          </div>
          <nav>
            <ul class="pagination mb-0">
              <li class="page-item" :class="{ disabled: paginationProductos.current_page === 1 }">
                <a class="page-link" href="#" @click.prevent="cambiarPaginaProductos(paginationProductos.current_page - 1)">
                  <i class="fas fa-chevron-left"></i>
                </a>
              </li>
              <li 
                v-for="page in getPaginationPages(paginationProductos)" 
                :key="page"
                class="page-item" 
                :class="{ active: page === paginationProductos.current_page, disabled: page === '...' }"
              >
                <a class="page-link" href="#" @click.prevent="page !== '...' && cambiarPaginaProductos(page)">
                  {{ page }}
                </a>
              </li>
              <li class="page-item" :class="{ disabled: paginationProductos.current_page === paginationProductos.last_page }">
                <a class="page-link" href="#" @click.prevent="cambiarPaginaProductos(paginationProductos.current_page + 1)">
                  <i class="fas fa-chevron-right"></i>
                </a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

    <!-- Modal Producto -->
    <div v-if="mostrarModalProducto" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header bg-primary text-white">
            <h5 class="modal-title">
              <i :class="productoEditando ? 'fas fa-edit' : 'fas fa-plus'" class="me-2"></i>
              {{ productoEditando ? 'Editar Producto' : 'Nuevo Producto' }}
            </h5>
            <button type="button" class="btn-close btn-close-white" @click="cerrarModalProducto"></button>
          </div>
          <form @submit.prevent="guardarProducto">
            <div class="modal-body">
              <div class="row">
                <div class="col-md-12 mb-3">
                  <label for="productoNombre" class="form-label">Nombre del producto *</label>
                  <input 
                    type="text" 
                    class="form-control" 
                    id="productoNombre"
                    v-model="formProducto.name"
                    required
                  >
                </div>
              </div>
              <div class="row">
                <div class="col-md-12 mb-3">
                  <label for="productoDescripcion" class="form-label">Descripción</label>
                  <textarea 
                    class="form-control" 
                    id="productoDescripcion"
                    v-model="formProducto.description"
                    rows="3"
                  ></textarea>
                </div>
              </div>
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="productoPrecio" class="form-label">Precio *</label>
                  <input 
                    type="number" 
                    class="form-control" 
                    id="productoPrecio"
                    v-model.number="formProducto.price"
                    step="0.01"
                    min="0"
                    required
                  >
                </div>
                <div class="col-md-6 mb-3">
                  <label for="productoCategoria" class="form-label">Categoría *</label>
                  <select 
                    class="form-select" 
                    id="productoCategoria"
                    v-model="formProducto.category_id"
                    required
                  >
                    <option value="">Seleccionar categoría</option>
                    <option v-for="cat in categoriasActivas" :key="cat.id" :value="cat.id">
                      {{ cat.name }}
                    </option>
                  </select>
                </div>
              </div>
              <div class="row">
                <div class="col-md-12 mb-3">
                  <div class="form-check">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="productoDisponible"
                      v-model="formProducto.is_available"
                    >
                    <label class="form-check-label" for="productoDisponible">
                      Producto disponible
                    </label>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" @click="cerrarModalProducto">
                Cancelar
              </button>
              <button type="submit" class="btn btn-primary" :disabled="guardandoProducto">
                <span v-if="guardandoProducto">
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
</template>

<script>
import axios from 'axios';

export default {
  name: 'ProductosPage',
  data() {
    return {
      productos: [],
      loadingProductos: false,
      searchProductos: '',
      filterCategoria: '',
      paginationProductos: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0
      },
      categoriasActivas: [],
      mostrarModalProducto: false,
      productoEditando: null,
      guardandoProducto: false,
      formProducto: {
        name: '',
        description: '',
        price: 0,
        category_id: '',
        is_available: true
      },
      searchTimeout: null
    }
  },
  
  async mounted() {
    await this.cargarCategoriasActivas();
    await this.cargarProductos();
  },
  
  methods: {
    async cargarCategoriasActivas() {
      try {
        const response = await axios.get('/api/admin/categorias/activas');
        this.categoriasActivas = response.data;
      } catch (error) {
        console.error('Error al cargar categorías activas:', error);
      }
    },
    
    async cargarProductos(page = 1) {
      this.loadingProductos = true;
      try {
        const params = {
          page,
          per_page: this.paginationProductos.per_page,
          search: this.searchProductos,
          category_id: this.filterCategoria
        };
        
        const response = await axios.get('/api/admin/productos', { params });
        
        this.productos = response.data.data;
        this.paginationProductos = {
          current_page: response.data.current_page,
          last_page: response.data.last_page,
          per_page: response.data.per_page,
          total: response.data.total
        };
      } catch (error) {
        console.error('Error al cargar productos:', error);
        alert('Error al cargar los productos');
      } finally {
        this.loadingProductos = false;
      }
    },
    
    buscarProductos() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.cargarProductos(1);
      }, 500);
    },
    
    cambiarPaginaProductos(page) {
      if (page >= 1 && page <= this.paginationProductos.last_page) {
        this.cargarProductos(page);
      }
    },
    
    cambiarPerPageProductos() {
      this.cargarProductos(1);
    },
    
    getPaginationPages(pagination) {
      const pages = [];
      const current = pagination.current_page;
      const last = pagination.last_page;
      
      if (last <= 7) {
        for (let i = 1; i <= last; i++) {
          pages.push(i);
        }
      } else {
        if (current <= 3) {
          for (let i = 1; i <= 5; i++) pages.push(i);
          pages.push('...');
          pages.push(last);
        } else if (current >= last - 2) {
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
    },
    
    abrirModalProducto(producto = null) {
      this.productoEditando = producto;
      if (producto) {
        this.formProducto = {
          name: producto.name,
          description: producto.description || '',
          price: producto.price,
          category_id: producto.category_id,
          is_available: producto.is_available
        };
      } else {
        this.formProducto = {
          name: '',
          description: '',
          price: 0,
          category_id: '',
          is_available: true
        };
      }
      this.mostrarModalProducto = true;
    },
    
    cerrarModalProducto() {
      this.mostrarModalProducto = false;
      this.productoEditando = null;
    },
    
    async guardarProducto() {
      this.guardandoProducto = true;
      try {
        if (this.productoEditando) {
          await axios.put(`/api/admin/productos/${this.productoEditando.id}`, this.formProducto);
          alert('Producto actualizado exitosamente');
        } else {
          await axios.post('/api/admin/productos', this.formProducto);
          alert('Producto creado exitosamente');
        }
        this.cerrarModalProducto();
        await this.cargarProductos(this.paginationProductos.current_page);
      } catch (error) {
        console.error('Error al guardar producto:', error);
        if (error.response?.data?.errors) {
          const errores = Object.values(error.response.data.errors).flat().join('\n');
          alert('Errores de validación:\n' + errores);
        } else {
          alert('Error al guardar el producto');
        }
      } finally {
        this.guardandoProducto = false;
      }
    },
    
    async eliminarProducto(producto) {
      if (!confirm(`¿Estás seguro de eliminar el producto "${producto.name}"?`)) {
        return;
      }
      
      try {
        await axios.delete(`/api/admin/productos/${producto.id}`);
        alert('Producto eliminado exitosamente');
        await this.cargarProductos(this.paginationProductos.current_page);
      } catch (error) {
        console.error('Error al eliminar producto:', error);
        alert('Error al eliminar el producto');
      }
    }
  }
}
</script>

<style scoped>
.productos-page {
  padding: 1rem;
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
  height: calc(100vh - 400px);
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
  width: 35%;
}

.table-wrapper th:nth-child(2),
.table-body-wrapper td:nth-child(2) {
  width: 15%;
}

.table-wrapper th:nth-child(3),
.table-body-wrapper td:nth-child(3) {
  width: 15%;
}

.table-wrapper th:nth-child(4),
.table-body-wrapper td:nth-child(4) {
  width: 20%;
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
