<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Clase extends Model
{
    use HasFactory;

    protected $table = 'clase';
    protected $primaryKey = 'idClase';
    public $timestamps = false;

    protected $fillable = [
        'diaSemana',
        'hora',
        'nombre',
        'descripcion',
        'cupoMax',
    ];

    protected $casts = [
        'hora' => 'datetime:H:i',
        'cupoMax' => 'integer',
    ];

    // Relación con inscripciones
    public function inscripciones()
    {
        return $this->hasMany(InscripcionClase::class, 'idClase');
    }

    // Método para obtener cupos disponibles
    public function getCuposDisponiblesAttribute()
    {
        $inscritos = $this->inscripciones()->count();
        return $this->cupoMax - $inscritos;
    }
}