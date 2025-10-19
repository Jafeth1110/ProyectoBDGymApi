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
        'tipoPago',
        'idDetalleMantenimiento',
        'descripcion',
    ];

    protected $casts = [
        'fechaPago' => 'date',
        'monto' => 'decimal:2',
        'idMembresia' => 'integer',
        'idMetodoPago' => 'integer',
        'idDetalleMantenimiento' => 'integer',
        'tipoPago' => 'string',
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

    // Relación con detalle de mantenimiento
    public function detalleMantenimiento()
    {
        return $this->belongsTo(DetalleMantenimiento::class, 'idDetalleMantenimiento');
    }

    // Accessor para formato de monto
    public function getMontoFormateadoAttribute()
    {
        return '₡' . number_format($this->monto, 2);
    }

    // Método para verificar si es pago de membresía
    public function esPagoMembresia()
    {
        return $this->tipoPago === 'membresia';
    }

    // Método para verificar si es pago de mantenimiento
    public function esPagoMantenimiento()
    {
        return $this->tipoPago === 'mantenimiento';
    }

    // Scope para pagos de membresía
    public function scopeMembresias($query)
    {
        return $query->where('tipoPago', 'membresia');
    }

    // Scope para pagos de mantenimiento
    public function scopeMantenimientos($query)
    {
        return $query->where('tipoPago', 'mantenimiento');
    }
}