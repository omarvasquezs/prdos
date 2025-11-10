<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ComprobanteCounter extends Model
{
    protected $table = 'comprobante_counter';
    
    protected $primaryKey = 'tipo_comprobante';
    
    public $incrementing = false;
    
    protected $keyType = 'string';
    
    public $timestamps = false;
    
    protected $fillable = [
        'tipo_comprobante',
        'last_value'
    ];
    
    public static function getNextNumber($tipo)
    {
        $counter = self::where('tipo_comprobante', $tipo)->first();
        if (!$counter) {
            $counter = self::create(['tipo_comprobante' => $tipo, 'last_value' => 0]);
        }
        
        $counter->increment('last_value');
        return $counter->last_value;
    }
}
