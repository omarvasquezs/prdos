<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PedidoItem extends Model
{
    /**
     * Los atributos que son asignables en masa.
     */
    protected $fillable = [
        'pedido_id',
        'producto_id',
        'cantidad',
        'precio_unitario'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'pedido_id' => 'integer',
        'producto_id' => 'integer',
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2'
    ];

    // No necesitamos timestamps para los items
    public $timestamps = false;

    /**
     * Relación: Un item pertenece a un pedido
     */
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }

    /**
     * Relación: Un item pertenece a un producto
     */
    public function producto(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'producto_id');
    }

    /**
     * Calcular el subtotal del item
     */
    public function getSubtotalAttribute(): float
    {
        return $this->cantidad * $this->precio_unitario;
    }

    /**
     * Accessor: Precio unitario formateado
     */
    public function getPrecioFormateadoAttribute(): string
    {
        return 'S/ ' . number_format($this->precio_unitario, 2);
    }

    /**
     * Accessor: Subtotal formateado
     */
    public function getSubtotalFormateadoAttribute(): string
    {
        return 'S/ ' . number_format($this->subtotal, 2);
    }
}
