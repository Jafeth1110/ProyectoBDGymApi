<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pago';
    protected $primaryKey = 'idPago';
    public $timestamps = false;

    protected $fillable = [
        'idMembresia',
        'idMetodoPago',
        'fechaPago',
        'monto',
    ];

    protected $casts = [
        'fechaPago' => 'date',
        'monto' => 'decimal:2',
        'idMembresia' => 'integer',
        'idMetodoPago' => 'integer',
    ];

    // Relación con membresía
    public function membresia()
    {
        return $this->belongsTo(Membresia::class, 'idMembresia');
    }

    // Relación con método de pago
    public function metodoPago()
    {
        return $this->belongsTo(MetodoPago::class, 'idMetodoPago');
    }

    // Accessor para formato de monto
    public function getMontoFormateadoAttribute()
    {
        return '₡' . number_format($this->monto, 2);
    }
}