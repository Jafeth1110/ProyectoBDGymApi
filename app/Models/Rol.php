<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'roles';
    protected $primaryKey = 'idRol';
    public $timestamps = false;

    protected $fillable = [
        'nombreRol',
        'descripcion'
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'idRol');
    }
}
