<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetodoPago extends Model
{
    protected $table = 'metodo_pago';
    
    public $timestamps = false;
    
    protected $fillable = [
        'nom_metodo_pago',
        'habilitado'
    ];
    
    protected $casts = [
        'habilitado' => 'boolean'
    ];
    
    public function comprobantes(): HasMany
    {
        return $this->hasMany(Comprobante::class, 'metodo_pago_id');
    }
    
    public function reporteIngresos(): HasMany
    {
        return $this->hasMany(ReporteIngreso::class, 'metodo_pago_id');
    }
    
    public function scopeHabilitados($query)
    {
        return $query->where('habilitado', true);
    }
}
