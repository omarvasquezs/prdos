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
        'nombre_cliente',
        'dni_ce_cliente',
        'observaciones',
        'last_updated_by',
        'cod_comprobante',
        'costo_total',
        'sunat_success',
        'sunat_error',
        'enlace_pdf',
        'enlace_xml',
        'enlace_cdr',
        'sunat_description',
        'anulado',
        'related_comprobante_id',
        'tipo_nota_credito',
        'sustento'
    ];
    
    protected $casts = [
        'fecha' => 'datetime',
        'fecha_actualizacion' => 'datetime',
        'costo_total' => 'float',
        'anulado' => 'boolean'
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
    
    public function relatedComprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'related_comprobante_id');
    }
    
    public function reporteIngresos(): HasMany
    {
        return $this->hasMany(ReporteIngreso::class, 'cod_comprobante', 'cod_comprobante');
    }
    
    public function generateCode()
    {
        $prefixMap = [
            'B' => 'B001-',
            'F' => 'F001-',
            'N' => 'NV001-'
        ];

        // Logic for Credit Note series
        if ($this->tipo_comprobante === 'C' && $this->relatedComprobante) {
            $parentType = $this->relatedComprobante->tipo_comprobante;
            if ($parentType === 'B') {
                $prefix = 'BC01-';
                // Use a separate counter for BC series if desired, or share 'C'
                // For simplicity let's use 'BC' as the counter key
                $counterKey = 'BC';
            } elseif ($parentType === 'F') {
                $prefix = 'FC01-';
                $counterKey = 'FC';
            } else {
                $prefix = 'NC01-'; // Fallback
                $counterKey = 'NC';
            }
            
            $nextNumber = ComprobanteCounter::getNextNumber($counterKey);
            $this->cod_comprobante = $prefix . $nextNumber;
            return $this->cod_comprobante;
        }

        $prefix = $prefixMap[$this->tipo_comprobante] ?? '';
        
        $nextNumber = ComprobanteCounter::getNextNumber($this->tipo_comprobante);
        $this->cod_comprobante = $prefix . $nextNumber;
        return $this->cod_comprobante;
    }
    
    public function getTipoComprobanteNameAttribute()
    {
        $tipos = [
            'B' => 'Boleta',
            'F' => 'Factura',
            'N' => 'Nota de Venta',
            'C' => 'Nota de Crédito'
        ];
        
        return $tipos[$this->tipo_comprobante] ?? 'Desconocido';
    }
}
