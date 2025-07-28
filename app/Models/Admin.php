<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Admin extends Model
{
    protected $table = 'admin';
    protected $primaryKey = 'idAdmin';
    public $timestamps = false;

    protected $fillable = [
        'idUsuario'
    ];

    protected $casts = [
        // Quitamos fechaAsignacion y permisos ya que no existen en la tabla actual
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'idUsuario');
    }

    public function mantenimientos()
    {
        return $this->hasMany(Mantenimiento::class, 'idAdmin');
    }

    public function detalleMantenimientos()
    {
        return $this->hasMany(DetalleMantenimiento::class, 'idAdmin');
    }
}
