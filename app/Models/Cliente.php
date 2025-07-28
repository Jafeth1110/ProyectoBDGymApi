<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'cliente';
    protected $primaryKey = 'idCliente';
    public $timestamps = false;

    protected $fillable = [
        'idUsuario',
        'fechaRegistro'
    ];

    protected $casts = [
        'fechaRegistro' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'idUsuario');
    }

    public function asistencias()
    {
        return $this->hasMany(Asistencia::class, 'idCliente');
    }

    public function inscripcionesClase()
    {
        return $this->hasMany(InscripcionClase::class, 'idCliente');
    }

    public function membresias()
    {
        return $this->hasMany(Membresia::class, 'idCliente');
    }

    public function metodosPago()
    {
        return $this->hasMany(MetodoPago::class, 'idCliente');
    }
}
