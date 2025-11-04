<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'detallemantenimiento';
    public $timestamps = false;

    protected $primaryKey = 'idDetalleMantenimiento';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'idAdmin',
        'idEquipo',
        'idMantenimiento',
        'fechaMantenimiento',
        'pagado',
        'fechaPago',
    ];

    protected $casts = [
        'idAdmin' => 'integer',
        'idEquipo' => 'integer',
        'idMantenimiento' => 'integer',
        'fechaMantenimiento' => 'date',
        'pagado' => 'boolean',
        'fechaPago' => 'datetime',
    ];

    protected $appends = ['estado_pago'];

    // Relaciones
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'idAdmin', 'idAdmin');
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'idEquipo', 'idEquipo');
    }

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class, 'idMantenimiento', 'idMantenimiento');
    }

    // Relación con pagos
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'idDetalleMantenimiento');
    }

    // Método para verificar si está pagado
    public function isPagado()
    {
        return $this->pagado === true || $this->pagado === 1;
    }

    // Método para obtener el estado de pago
    public function getEstadoPagoAttribute()
    {
        return $this->isPagado() ? 'Pagado' : 'Pendiente de pago';
    }

    // Scope para mantenimientos pagados
    public function scopePagados($query)
    {
        return $query->where('pagado', 1);
    }

    // Scope para mantenimientos pendientes de pago
    public function scopePendientesPago($query)
    {
        return $query->where('pagado', 0);
    }
}
