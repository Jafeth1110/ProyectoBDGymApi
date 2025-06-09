<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipo extends Model
{
    use HasFactory;

    // Nombre de la tabla
    protected $table = 'equipo';

    // No tiene timestamps (created_at, updated_at)
    public $timestamps = false;

    // Clave primaria
    protected $primaryKey = 'idEquipo';
    public $incrementing = true;
    protected $keyType = 'int';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'nombre',
        'tipo',
        'estado',
        'cantidad',
    ];

    // Si necesitas ocultar atributos al serializar, agrégalos aquí
    // protected $hidden = [];

    // Si necesitas castear atributos, agrégalos aquí
    protected $casts = [
        'estado' => 'integer',
        'cantidad' => 'integer',
    ];
}
