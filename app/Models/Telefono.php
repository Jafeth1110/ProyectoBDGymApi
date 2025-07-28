<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Telefono extends Model
{
    protected $table = 'telefonos';
    protected $primaryKey = 'idTelefono';
    
    protected $fillable = [
        'idUsuario',
        'telefono',
        'tipoTel',
        'idRol'
    ];

    protected $casts = [
        'idUsuario' => 'integer',
        'idRol' => 'integer'
    ];

    // Relación con el usuario
    public function user()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }

    // Relación con el rol
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'idRol', 'idRol');
    }
}
