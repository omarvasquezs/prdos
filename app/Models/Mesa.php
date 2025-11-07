<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mesa extends Model
{
    /**
     * Los atributos que son asignables en masa.
     */
    protected $fillable = [
        'num_mesa',
        'estado'
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'num_mesa' => 'integer'
    ];

    // No necesitamos timestamps automáticos para esta tabla
    public $timestamps = false;

    /**
     * Relación: Una mesa puede tener muchos pedidos
     */
    public function pedidos(): HasMany
    {
        return $this->hasMany(Pedido::class, 'mesa_id');
    }

    /**
     * Relación: Obtener el pedido activo (abierto) de la mesa
     */
    public function pedidoActivo()
    {
        return $this->hasOne(Pedido::class, 'mesa_id')->where('estado', 'A');
    }

    /**
     * Método helper: Obtener el pedido activo como objeto
     */
    public function getPedidoActivo()
    {
        return $this->pedidos()->where('estado', 'A')->first();
    }

    /**
     * Verificar si la mesa está disponible
     */
    public function isDisponible(): bool
    {
        return $this->estado === 'D';
    }

    /**
     * Verificar si la mesa está ocupada
     */
    public function isOcupada(): bool
    {
        return $this->estado === 'O';
    }

    /**
     * Ocupar la mesa
     */
    public function ocupar(): void
    {
        $this->update(['estado' => 'O']);
    }

    /**
     * Liberar la mesa
     */
    public function liberar(): void
    {
        $this->update(['estado' => 'D']);
    }

    /**
     * Scope: Mesas disponibles
     */
    public function scopeDisponibles($query)
    {
        return $query->where('estado', 'D');
    }

    /**
     * Scope: Mesas ocupadas
     */
    public function scopeOcupadas($query)
    {
        return $query->where('estado', 'O');
    }

    /**
     * Accessor: Nombre de la mesa formateado
     */
    public function getNombreAttribute(): string
    {
        return "Mesa {$this->num_mesa}";
    }

    /**
     * Accessor: Estado formateado
     */
    public function getEstadoTextoAttribute(): string
    {
        return match($this->estado) {
            'D' => 'Disponible',
            'O' => 'Ocupada',
            default => 'Desconocido'
        };
    }

    /**
     * Accessor: Color del estado para la UI
     */
    public function getEstadoColorAttribute(): string
    {
        return match($this->estado) {
            'D' => 'success',
            'O' => 'danger',
            default => 'secondary'
        };
    }
}
