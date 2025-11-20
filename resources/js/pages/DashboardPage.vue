<template>
  <AdminLayout>
    <div class="dashboard-page">
      <!-- Header -->
      <div class="dashboard-header mb-4">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <h1 class="page-title mb-1">
              <i class="fas fa-cogs me-2 text-warning"></i>
              Panel de Administración
            </h1>
            <p class="text-muted mb-0">Gestiona productos y categorías del sistema</p>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <ul class="nav nav-tabs nav-tabs-custom mb-4" role="tablist">
        <li class="nav-item" role="presentation">
          <button 
            class="nav-link" 
            :class="{ active: activeTab === 'productos' }"
            @click="activeTab = 'productos'"
            type="button"
          >
            <i class="fas fa-box me-2"></i>
            Productos
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button 
            class="nav-link" 
            :class="{ active: activeTab === 'categorias' }"
            @click="activeTab = 'categorias'"
            type="button"
          >
            <i class="fas fa-tags me-2"></i>
            Categorías
          </button>
        </li>
      </ul>

      <!-- Tab Content -->
      <div class="tab-content">
        <!-- Productos Tab -->
        <div v-show="activeTab === 'productos'" class="tab-pane show active">
          <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>
                Lista de Productos
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
              <div class="table-responsive">
                <table class="table table-hover align-middle">
                  <thead class="table-light">
                    <tr>
                      <th>Nombre</th>
                      <th>Categoría</th>
                      <th>Precio</th>
                      <th>Estado</th>
                      <th class="text-end">Acciones</th>
                    </tr>
                  </thead>
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
        </div>

        <!-- Categorías Tab -->
        <div v-show="activeTab === 'categorias'" class="tab-pane show">
          <div class="card shadow-sm">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <h5 class="card-title mb-0">
                <i class="fas fa-list me-2"></i>
                Lista de Categorías
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
                      <th>Productos</th>
                      <th>Estado</th>
                      <th class="text-end">Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr v-if="loadingCategorias">
                      <td colspan="5" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                          <span class="visually-hidden">Cargando...</span>
                        </div>
                      </td>
                    </tr>
                    <tr v-else-if="categorias.length === 0">
                      <td colspan="5" class="text-center text-muted py-4">
                        No hay categorías registradas
                      </td>
                    </tr>
                    <tr v-else v-for="categoria in categorias" :key="categoria.id">
                      <td><strong>{{ categoria.name }}</strong></td>
                      <td>{{ categoria.description || '-' }}</td>
                      <td>
                        <span class="badge bg-primary">{{ categoria.products_count }} productos</span>
                      </td>
                      <td>
                        <span class="badge" :class="categoria.is_active ? 'bg-success' : 'bg-secondary'">
                          {{ categoria.is_active ? 'Activa' : 'Inactiva' }}
                        </span>
                      </td>
                      <td class="text-end">
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
                          :disabled="categoria.products_count > 0"
                        >
                          <i class="fas fa-trash"></i>
                        </button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
              
              <!-- Paginación -->
              <div v-if="paginationCategorias.last_page > 1" class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted">
                  Mostrando {{ ((paginationCategorias.current_page - 1) * paginationCategorias.per_page) + 1 }} 
                  a {{ Math.min(paginationCategorias.current_page * paginationCategorias.per_page, paginationCategorias.total) }} 
                  de {{ paginationCategorias.total }} categorías
                </div>
                <nav>
                  <ul class="pagination mb-0">
                    <li class="page-item" :class="{ disabled: paginationCategorias.current_page === 1 }">
                      <a class="page-link" href="#" @click.prevent="cambiarPaginaCategorias(paginationCategorias.current_page - 1)">
                        <i class="fas fa-chevron-left"></i>
                      </a>
                    </li>
                    <li 
                      v-for="page in getPaginationPages(paginationCategorias)" 
                      :key="page"
                      class="page-item" 
                      :class="{ active: page === paginationCategorias.current_page, disabled: page === '...' }"
                    >
                      <a class="page-link" href="#" @click.prevent="page !== '...' && cambiarPaginaCategorias(page)">
                        {{ page }}
                      </a>
                    </li>
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
                    <label for="productoNombre" class="form-label">Nombre *</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      id="productoNombre"
                      v-model="formProducto.name"
                      required
                    >
                  </div>
                </div>
                <div class="mb-3">
                  <label for="productoDescripcion" class="form-label">Descripción</label>
                  <textarea 
                    class="form-control" 
                    id="productoDescripcion"
                    v-model="formProducto.description"
                    rows="2"
                  ></textarea>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="productoPrecio" class="form-label">Precio (S/) *</label>
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
                <div class="mb-3">
                  <div class="form-check form-switch">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="productoDisponible"
                      v-model="formProducto.is_available"
                    >
                    <label class="form-check-label" for="productoDisponible">
                      Disponible para la venta
                    </label>
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

      <!-- Modal Categoría -->
      <div v-if="mostrarModalCategoria" class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.5);">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header bg-primary text-white">
              <h5 class="modal-title">
                <i :class="categoriaEditando ? 'fas fa-edit' : 'fas fa-plus'" class="me-2"></i>
                {{ categoriaEditando ? 'Editar Categoría' : 'Nueva Categoría' }}
              </h5>
              <button type="button" class="btn-close btn-close-white" @click="cerrarModalCategoria"></button>
            </div>
            <form @submit.prevent="guardarCategoria">
              <div class="modal-body">
                <div class="row">
                  <div class="col-md-8 mb-3">
                    <label for="categoriaNombre" class="form-label">Nombre *</label>
                    <input 
                      type="text" 
                      class="form-control" 
                      id="categoriaNombre"
                      v-model="formCategoria.name"
                      required
                    >
                  </div>
                  <div class="col-md-4 mb-3">
                    <label for="categoriaOrden" class="form-label">Orden</label>
                    <input 
                      type="number" 
                      class="form-control" 
                      id="categoriaOrden"
                      v-model.number="formCategoria.order"
                      min="0"
                    >
                  </div>
                </div>
                <div class="mb-3">
                  <label for="categoriaDescripcion" class="form-label">Descripción</label>
                  <textarea 
                    class="form-control" 
                    id="categoriaDescripcion"
                    v-model="formCategoria.description"
                    rows="2"
                  ></textarea>
                </div>
                <div class="mb-3">
                  <div class="form-check form-switch">
                    <input 
                      class="form-check-input" 
                      type="checkbox" 
                      id="categoriaActiva"
                      v-model="formCategoria.is_active"
                    >
                    <label class="form-check-label" for="categoriaActiva">
                      Categoría activa
                    </label>
                  </div>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" @click="cerrarModalCategoria">
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
  </AdminLayout>
</template>

<script>
import AdminLayout from '@/layouts/AdminLayout.vue';
import axios from 'axios';

export default {
  name: 'DashboardPage',
  components: {
    AdminLayout
  },
  data() {
    return {
      activeTab: 'productos',
      
      // Productos
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
      
      // Categorías
      categorias: [],
      categoriasActivas: [], // Para el dropdown de filtro
      loadingCategorias: false,
      searchCategorias: '',
      paginationCategorias: {
        current_page: 1,
        last_page: 1,
        per_page: 10,
        total: 0
      },
      
      // Modales
      mostrarModalProducto: false,
      mostrarModalCategoria: false,
      productoEditando: null,
      categoriaEditando: null,
      guardandoProducto: false,
      guardandoCategoria: false,
      
      // Formularios
      formProducto: {
        name: '',
        description: '',
        price: 0,
        category_id: '',
        is_available: true
      },
      formCategoria: {
        name: '',
        description: '',
        is_active: true,
        order: 0
      },
      
      searchTimeout: null
    }
  },
  
  async mounted() {
    console.log('DashboardPage mounted');
    await this.cargarCategoriasActivas();
    await this.cargarCategorias();
    await this.cargarProductos();
    console.log('Productos cargados:', this.productos.length);
    console.log('Categorías cargadas:', this.categorias.length);
  },
  
  methods: {
    // Cargar categorías activas para el dropdown
    async cargarCategoriasActivas() {
      try {
        const response = await axios.get('/api/admin/categorias/activas');
        this.categoriasActivas = response.data;
      } catch (error) {
        console.error('Error al cargar categorías activas:', error);
      }
    },
    
    // Productos
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
    },
    
    // Categorías
    async cargarCategorias(page = 1) {
      this.loadingCategorias = true;
      try {
        const params = {
          page,
          per_page: this.paginationCategorias.per_page,
          search: this.searchCategorias
        };
        
        const response = await axios.get('/api/admin/categorias', { params });
        
        if (response.data.data) {
          // Respuesta paginada
          this.categorias = response.data.data;
          this.paginationCategorias = {
            current_page: response.data.current_page,
            last_page: response.data.last_page,
            per_page: response.data.per_page,
            total: response.data.total
          };
        } else {
          // Respuesta sin paginar (backward compatibility)
          this.categorias = response.data;
        }
      } catch (error) {
        console.error('Error al cargar categorías:', error);
        alert('Error al cargar las categorías');
      } finally {
        this.loadingCategorias = false;
      }
    },
    
    buscarCategorias() {
      clearTimeout(this.searchTimeout);
      this.searchTimeout = setTimeout(() => {
        this.cargarCategorias(1);
      }, 500);
    },
    
    cambiarPaginaCategorias(page) {
      if (page >= 1 && page <= this.paginationCategorias.last_page) {
        this.cargarCategorias(page);
      }
    },
    
    cambiarPerPageCategorias() {
      this.cargarCategorias(1);
    },
    
    abrirModalCategoria(categoria = null) {
      this.categoriaEditando = categoria;
      if (categoria) {
        this.formCategoria = {
          name: categoria.name,
          description: categoria.description || '',
          is_active: categoria.is_active
        };
      } else {
        this.formCategoria = {
          name: '',
          description: '',
          is_active: true
        };
      }
      this.mostrarModalCategoria = true;
    },
    
    cerrarModalCategoria() {
      this.mostrarModalCategoria = false;
      this.categoriaEditando = null;
    },
    
    async guardarCategoria() {
      this.guardandoCategoria = true;
      try {
        if (this.categoriaEditando) {
          await axios.put(`/api/admin/categorias/${this.categoriaEditando.id}`, this.formCategoria);
          alert('Categoría actualizada exitosamente');
        } else {
          await axios.post('/api/admin/categorias', this.formCategoria);
          alert('Categoría creada exitosamente');
        }
        this.cerrarModalCategoria();
        await this.cargarCategorias();
        await this.cargarCategoriasActivas(); // Actualizar dropdown
        if (this.activeTab === 'productos') {
          await this.cargarProductos(this.paginationProductos.current_page);
        }
      } catch (error) {
        console.error('Error al guardar categoría:', error);
        if (error.response?.data?.errors) {
          const errores = Object.values(error.response.data.errors).flat().join('\n');
          alert('Errores de validación:\n' + errores);
        } else {
          alert('Error al guardar la categoría');
        }
      } finally {
        this.guardandoCategoria = false;
      }
    },
    
    async eliminarCategoria(categoria) {
      if (categoria.products_count > 0) {
        alert('No se puede eliminar una categoría que tiene productos asociados');
        return;
      }
      
      if (!confirm(`¿Estás seguro de eliminar la categoría "${categoria.name}"?`)) {
        return;
      }
      
      try {
        await axios.delete(`/api/admin/categorias/${categoria.id}`);
        alert('Categoría eliminada exitosamente');
        await this.cargarCategorias();
      } catch (error) {
        console.error('Error al eliminar categoría:', error);
        alert(error.response?.data?.error || 'Error al eliminar la categoría');
      }
    }
  }
}
</script>

<style scoped>
.dashboard-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  padding: 2rem;
}

.dashboard-header {
  background: white;
  padding: 1.5rem;
  border-radius: 0.5rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.page-title {
  font-size: 1.75rem;
  font-weight: 600;
  color: #212529;
}

.nav-tabs-custom {
  border-bottom: 2px solid #dee2e6;
}

.nav-tabs-custom .nav-link {
  border: none;
  border-bottom: 3px solid transparent;
  color: #6c757d;
  font-weight: 500;
  padding: 0.75rem 1.5rem;
  transition: all 0.2s;
}

.nav-tabs-custom .nav-link:hover {
  border-color: transparent;
  color: #0d6efd;
}

.nav-tabs-custom .nav-link.active {
  color: #0d6efd;
  border-bottom-color: #0d6efd;
  background: transparent;
}

.card {
  border: none;
  border-radius: 0.5rem;
}

.card-header {
  border-bottom: 1px solid #dee2e6;
  padding: 1rem 1.5rem;
}

.table {
  margin-bottom: 0;
}

.table thead th {
  border-bottom: 2px solid #dee2e6;
  font-weight: 600;
  color: #495057;
  text-transform: uppercase;
  font-size: 0.875rem;
  letter-spacing: 0.5px;
}

.table tbody tr {
  transition: background-color 0.2s;
}

.table tbody tr:hover {
  background-color: #f8f9fa;
}

.badge {
  padding: 0.375rem 0.75rem;
  font-weight: 500;
}

.btn-sm {
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
}

.modal-content {
  border: none;
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}

.modal-header {
  border-bottom: 1px solid rgba(255, 255, 255, 0.2);
}

.modal-footer {
  border-top: 1px solid #dee2e6;
}

.form-label {
  font-weight: 500;
  color: #495057;
  margin-bottom: 0.5rem;
}

.form-control:focus,
.form-select:focus {
  border-color: #0d6efd;
  box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
</style>
