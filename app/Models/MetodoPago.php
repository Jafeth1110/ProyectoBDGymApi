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
        'idCliente',
        'nombre',
    ];

    protected $casts = [
        'idCliente' => 'integer',
    ];

    // Relación con cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idCliente');
    }

    // Relación con pagos
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'idMetodoPago');
    }
}