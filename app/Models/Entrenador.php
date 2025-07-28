<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Entrenador extends Model
{
    protected $table = 'entrenador';
    protected $primaryKey = 'idEntrenador';
    public $timestamps = false;

    protected $fillable = [
        'idUsuario',
        'especialidad'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'idUsuario');
    }

    public function inscripcionesClase()
    {
        return $this->hasMany(InscripcionClase::class, 'idEntrenador');
    }
}
