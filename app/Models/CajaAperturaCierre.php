<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CajaAperturaCierre extends Model
{
    protected $table = 'caja_apertura_cierre';
    
    public $timestamps = false;
    
    protected $fillable = [
        'datetime_apertura',
        'monto_apertura',
        'monto_apertura_billetes',
        'monto_apertura_monedas',
        'id_usuario_apertura',
        'datetime_cierre',
        'monto_cierre',
        'monto_cierre_billetes',
        'monto_cierre_monedas',
        'id_usuario_cierre'
    ];
    
    protected $casts = [
        'datetime_apertura' => 'datetime',
        'datetime_cierre' => 'datetime',
        'monto_apertura' => 'decimal:2',
        'monto_apertura_billetes' => 'decimal:2',
        'monto_apertura_monedas' => 'decimal:2',
        'monto_cierre' => 'decimal:2',
        'monto_cierre_billetes' => 'decimal:2',
        'monto_cierre_monedas' => 'decimal:2'
    ];
    
    public function usuarioApertura(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario_apertura');
    }
    
    public function usuarioCierre(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_usuario_cierre');
    }
}
