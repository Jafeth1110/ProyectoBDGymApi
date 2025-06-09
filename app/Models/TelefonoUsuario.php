<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelefonoUsuario extends Model
{
    use HasFactory;

    protected $table = 'telefonousuario';
    public $timestamps = false;

    protected $primaryKey = 'idTelefonoUsuario';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'idUsuario',
        'tipoTel',
        'telefono',
    ];

    protected $casts = [
        'idUsuario' => 'integer',
    ];

    // Relación con el usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'idUsuario', 'idUsuario');
    }
}
