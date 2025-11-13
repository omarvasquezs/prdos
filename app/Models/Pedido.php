<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pedido extends Model
{
    /**
     * Los atributos que son asignables en masa.
     */
    protected $fillable = [
        'tipo_atencion',
        'mesa_id',
        'comensales',
        'cliente_nombre',
        'cliente_telefono',
        'direccion_entrega',
        'notas',
        'estado',
        'fecha_apertura',
        'fecha_cierre',
        'total'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'mesa_id' => 'integer',
        'comensales' => 'integer',
        'fecha_apertura' => 'datetime',
        'fecha_cierre' => 'datetime',
        'total' => 'decimal:2'
    ];

    // No necesitamos timestamps automáticos, usamos fecha_apertura y fecha_cierre
    public $timestamps = false;

    /**
     * Relación: Un pedido pertenece a una mesa
     */
    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class, 'mesa_id');
    }

    /**
     * Relación: Un pedido puede tener muchos items
     */
    public function items(): HasMany
    {
        return $this->hasMany(PedidoItem::class, 'pedido_id');
    }

    /**
     * Verificar si el pedido está abierto
     */
    public function isAbierto(): bool
    {
        return $this->estado === 'A';
    }

    /**
     * Verificar si el pedido está cerrado
     */
    public function isCerrado(): bool
    {
        return $this->estado === 'C';
    }

    /**
     * Verificar si el pedido está cancelado
     */
    public function isCancelado(): bool
    {
        return $this->estado === 'X';
    }

    /**
     * Cerrar el pedido
     */
    public function cerrar(): void
    {
        $this->update([
            'estado' => 'C',
            'fecha_cierre' => now(),
            'total' => $this->calcularTotal()
        ]);
        
        // Liberar la mesa solo si es pedido presencial
        if ($this->tipo_atencion === 'P' && $this->mesa) {
            $this->mesa->liberar();
        }
    }

    /**
     * Cancelar el pedido
     */
    public function cancelar(): void
    {
        $this->update([
            'estado' => 'X',
            'fecha_cierre' => now()
        ]);
        
        // Liberar la mesa solo si es pedido presencial
        if ($this->tipo_atencion === 'P' && $this->mesa) {
            $this->mesa->liberar();
        }
    }

    /**
     * Calcular el total del pedido
     */
    public function calcularTotal(): float
    {
        return $this->items->sum(function ($item) {
            return $item->cantidad * $item->precio_unitario;
        });
    }

    /**
     * Scope: Pedidos abiertos
     */
    public function scopeAbiertos($query)
    {
        return $query->where('estado', 'A');
    }

    /**
     * Scope: Pedidos cerrados
     */
    public function scopeCerrados($query)
    {
        return $query->where('estado', 'C');
    }

    /**
     * Scope: Pedidos cancelados
     */
    public function scopeCancelados($query)
    {
        return $query->where('estado', 'X');
    }

    /**
     * Verificar si es pedido presencial
     */
    public function isPresencial(): bool
    {
        return $this->tipo_atencion === 'P';
    }

    /**
     * Verificar si es pedido delivery
     */
    public function isDelivery(): bool
    {
        return $this->tipo_atencion === 'D';
    }

    /**
     * Verificar si es pedido recojo
     */
    public function isRecojo(): bool
    {
        return $this->tipo_atencion === 'R';
    }

    /**
     * Accessor: Tipo de atención formateado
     */
    public function getTipoAtencionTextoAttribute(): string
    {
        return match($this->tipo_atencion) {
            'P' => 'Presencial',
            'D' => 'Delivery',
            'R' => 'Recojo',
            default => 'Desconocido'
        };
    }

    /**
     * Accessor: Estado formateado
     */
    public function getEstadoTextoAttribute(): string
    {
        return match($this->estado) {
            'A' => 'Abierto',
            'C' => 'Cerrado',
            'X' => 'Cancelado',
            default => 'Desconocido'
        };
    }

    /**
     * Accessor: Duración del pedido
     */
    public function getDuracionAttribute(): ?string
    {
        if (!$this->fecha_cierre) {
            return $this->fecha_apertura->diffForHumans();
        }
        
        return $this->fecha_apertura->diffInMinutes($this->fecha_cierre) . ' minutos';
    }
}
