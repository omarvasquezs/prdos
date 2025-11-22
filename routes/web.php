<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\MesaController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CajaController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\ExportController;
use App\Http\Controllers\ComprobanteController;

// Rutas de autenticación
Route::get('/sanctum/csrf-cookie', function () {
    return response()->json(['message' => 'CSRF cookie set']);
})->name('csrf-cookie');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
Route::post('/logout', [AuthController::class, 'logout'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Rutas protegidas (requieren autenticación)
Route::middleware('auth')->group(function () {
    // Ruta raíz redirige según el rol
    Route::get('/', [DashboardController::class, 'redirectToRole'])->name('home');
    
    // Dashboard solo para administrador
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('role:administrador')->name('dashboard');
    
    // Route to show the role selection SPA route (served by the admin SPA)
    Route::get('/select-role', [DashboardController::class, 'index']);
    Route::get('/test', [DashboardController::class, 'index']);
    // Caja page routes
    Route::get('/caja', [DashboardController::class, 'index']);
    Route::get('/caja/pedido/{id}', [DashboardController::class, 'index']);
});

// API routes para Vue
Route::middleware(['web', 'auth'])->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->group(function () {
    Route::get('/api/user', [AuthController::class, 'user']);
    Route::post('/select-role', [AuthController::class, 'setActiveRole']);
    Route::post('/api/set-active-role', [AuthController::class, 'setActiveRole']);
    
    // API routes for mesas
    Route::get('/api/mesas', [MesaController::class, 'index']);
    Route::get('/api/mesas/{mesa}', [MesaController::class, 'show']);
    Route::post('/api/mesas/{mesa}/ocupar', [MesaController::class, 'ocupar']);
    Route::post('/api/mesas/{mesa}/liberar', [MesaController::class, 'liberar']);
    
    // API routes for pedidos
    Route::get('/api/pedidos/{pedido}', [MesaController::class, 'getPedido']);
    Route::post('/api/pedidos/{pedido}/items', [MesaController::class, 'agregarItem']);
    Route::delete('/api/pedidos/{pedido}/items/{item}', [MesaController::class, 'eliminarItem']);
    Route::post('/api/pedidos/{pedido}/cobrar', [MesaController::class, 'cobrarPedido']);
    Route::post('/api/pedidos/{pedido}/cancelar', [MesaController::class, 'cancelarPedido']);
    
    // API routes for products
    Route::get('/api/productos', [ProductController::class, 'index']);
    Route::get('/api/productos/categorias', [ProductController::class, 'categories']);
    
    // Admin product management routes
    Route::get('/api/admin/productos', [ProductController::class, 'adminIndex']);
    Route::get('/api/admin/productos/{product}', [ProductController::class, 'show']);
    Route::post('/api/admin/productos', [ProductController::class, 'store']);
    Route::put('/api/admin/productos/{product}', [ProductController::class, 'update']);
    Route::delete('/api/admin/productos/{product}', [ProductController::class, 'destroy']);
    
    // Admin category management routes
    Route::get('/api/admin/categorias/activas', [CategoryController::class, 'listActive']);
    Route::get('/api/admin/categorias', [CategoryController::class, 'index']);
    Route::get('/api/admin/categorias/{category}', [CategoryController::class, 'show']);
    Route::post('/api/admin/categorias', [CategoryController::class, 'store']);
    Route::put('/api/admin/categorias/{category}', [CategoryController::class, 'update']);
    Route::delete('/api/admin/categorias/{category}', [CategoryController::class, 'destroy']);
    
    // Admin user management routes
    Route::get('/api/admin/usuarios/roles', [UserController::class, 'getRoles']);
    Route::get('/api/admin/usuarios', [UserController::class, 'index']);
    Route::get('/api/admin/usuarios/{user}', [UserController::class, 'show']);
    Route::post('/api/admin/usuarios', [UserController::class, 'store']);
    Route::put('/api/admin/usuarios/{user}', [UserController::class, 'update']);
    Route::post('/api/admin/usuarios/{user}/change-password', [UserController::class, 'changePassword']);
    Route::delete('/api/admin/usuarios/{user}', [UserController::class, 'destroy']);
    
    // API routes for comprobantes
    Route::post('/api/pedidos/{pedidoId}/comprobante', [ComprobanteController::class, 'create']);
    Route::get('/api/metodos-pago', [ComprobanteController::class, 'getMetodosPago']);
    Route::get('/comprobante/{codComprobante}', [ComprobanteController::class, 'show']);

    // API routes for caja apertura/cierre
    Route::get('/api/caja/status', [CajaController::class, 'status']);
    Route::post('/api/caja/abrir', [CajaController::class, 'abrir']);
    Route::post('/api/caja/cerrar', [CajaController::class, 'cerrar']);
    Route::get('/api/caja/movimientos', [CajaController::class, 'movimientos']);
    Route::get('/api/export', [ExportController::class, 'export']);

    // API routes for delivery/recojo pedidos
    Route::get('/api/pedidos-cola', [PedidoController::class, 'index']);
    Route::post('/api/pedidos-cola', [PedidoController::class, 'store']);
    Route::get('/api/pedidos-cola/{pedido}', [PedidoController::class, 'show']);
    Route::post('/api/pedidos-cola/{pedido}/items', [PedidoController::class, 'addItem']);
    Route::delete('/api/pedidos-cola/{pedido}/items/{item}', [PedidoController::class, 'removeItem']);
    Route::post('/api/pedidos-cola/{pedido}/cancelar', [PedidoController::class, 'cancel']);
    Route::post('/api/pedidos-cola/{pedido}/estado-entrega', [PedidoController::class, 'cambiarEstadoEntrega']);
    Route::post('/api/pedidos-cola/{pedido}/marcar-pagado', [PedidoController::class, 'marcarPagado']);
});

// Catch-all route para SPA - debe ir al final
Route::middleware('auth')->get('/{any}', [DashboardController::class, 'index'])->where('any', '.*');
