<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    use HasFactory;

    protected $table = 'metodopago';
    protected $primaryKey = 'idMetodoPago';
    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'comision',
        'requiereAutorizacion',
        'estado',
    ];

    protected $casts = [
        'comision' => 'decimal:2',
        'requiereAutorizacion' => 'boolean',
        'estado' => 'boolean',
    ];

    // Reglas de validación
    public static function rules($id = null)
    {
        return [
            'nombre' => 'required|string|max:45|unique:metodopago,nombre' . ($id ? ",$id,idMetodoPago" : ''),
            'descripcion' => 'nullable|string|max:100',
            'comision' => 'required|numeric|min:0|max:100',
            'requiereAutorizacion' => 'required|boolean',
            'estado' => 'required|boolean',
        ];
    }

    // Relación con pagos
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'idMetodoPago');
    }

    // Scope para métodos activos
    public function scopeActivos($query)
    {
        return $query->where('estado', 1);
    }
}