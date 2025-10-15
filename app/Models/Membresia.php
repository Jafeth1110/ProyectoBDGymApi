<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membresia extends Model
{
    use HasFactory;

    protected $table = 'membresia';
    protected $primaryKey = 'idMembresia';
    public $timestamps = false;

    protected $fillable = [
        'idCliente',
        'tipoMem',
        'fechaVenc',
        'fechaInicio',
    ];

    protected $casts = [
        'fechaVenc' => 'date',
        'fechaInicio' => 'date',
        'idCliente' => 'integer',
    ];

    protected $appends = ['estado'];

    // Relación con cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idCliente');
    }

    // Relación con pagos
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'idMembresia');
    }

    // Accessor para el estado (activo/inactivo)
    public function getEstadoAttribute()
    {
        return $this->fechaVenc >= now()->toDateString() ? 1 : 0;
    }

    // Método para verificar si está activa
    public function isActiva()
    {
        return $this->estado === 1;
    }
}