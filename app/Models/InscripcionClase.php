<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InscripcionClase extends Model
{
    use HasFactory;

    protected $table = 'inscripcionclase';
    protected $primaryKey = 'idInscripcionClase';
    public $timestamps = false;

    protected $fillable = [
        'idCliente',
        'idEntrenador', 
        'idClase',
        'fechaInscripcion',
    ];

    protected $casts = [
        'fechaInscripcion' => 'date',
        'idCliente' => 'integer',
        'idEntrenador' => 'integer',
        'idClase' => 'integer',
    ];

    // Relaciones
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idCliente');
    }

    public function entrenador()
    {
        return $this->belongsTo(Entrenador::class, 'idEntrenador');
    }

    public function clase()
    {
        return $this->belongsTo(Clase::class, 'idClase');
    }
}