<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComprobantePago extends Model
{
    protected $table = 'comprobante_pagos';

    protected $fillable = [
        'comprobante_id',
        'metodo_pago_id',
        'monto',
        'monto_recibido',
        'vuelto'
    ];

    protected $casts = [
        'comprobante_id' => 'integer',
        'metodo_pago_id' => 'integer',
        'monto' => 'decimal:2',
        'monto_recibido' => 'decimal:2',
        'vuelto' => 'decimal:2'
    ];

    public function comprobante(): BelongsTo
    {
        return $this->belongsTo(Comprobante::class, 'comprobante_id');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }
}
