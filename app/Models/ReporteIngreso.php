<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReporteIngreso extends Model
{
    protected $table = 'reporte_ingresos';
    
    public $timestamps = false;
    
    protected $fillable = [
        'cod_comprobante',
        'metodo_pago_id',
        'fecha',
        'costo_total'
    ];
    
    protected $casts = [
        'fecha' => 'datetime',
        'costo_total' => 'float'
    ];
    
    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }
    
    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'cod_comprobante', 'cod_comprobante');
    }
}
