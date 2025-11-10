<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comprobante extends Model
{
    protected $table = 'comprobantes';
    
    const CREATED_AT = 'fecha';
    const UPDATED_AT = 'fecha_actualizacion';
    
    protected $fillable = [
        'tipo_comprobante',
        'user_id',
        'pedido_id',
        'fecha',
        'metodo_pago_id',
        'num_ruc',
        'razon_social',
        'observaciones',
        'last_updated_by',
        'cod_comprobante',
        'costo_total'
    ];
    
    protected $casts = [
        'fecha' => 'datetime',
        'fecha_actualizacion' => 'datetime',
        'costo_total' => 'float'
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    public function pedido(): BelongsTo
    {
        return $this->belongsTo(Pedido::class, 'pedido_id');
    }
    
    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }
    
    public function lastUpdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_updated_by');
    }
    
    public function reporteIngresos(): HasMany
    {
        return $this->hasMany(ReporteIngreso::class, 'cod_comprobante', 'cod_comprobante');
    }
    
    public function generateCode()
    {
        $nextNumber = ComprobanteCounter::getNextNumber($this->tipo_comprobante);
        $this->cod_comprobante = $this->tipo_comprobante . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        return $this->cod_comprobante;
    }
    
    public function getTipoComprobanteNameAttribute()
    {
        $tipos = [
            'B' => 'Boleta',
            'F' => 'Factura',
            'N' => 'Nota de Venta'
        ];
        
        return $tipos[$this->tipo_comprobante] ?? 'Desconocido';
    }
}
