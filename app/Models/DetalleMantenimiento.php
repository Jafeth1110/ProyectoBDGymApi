<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetalleMantenimiento extends Model
{
    use HasFactory;

    protected $table = 'detallemantenimiento';
    public $timestamps = false;

    protected $primaryKey = 'idDetalleMantenimiento';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'idAdmin',
        'idEquipo',
        'idMantenimiento',
        'fechaMantenimiento',
    ];

    protected $casts = [
        'idAdmin' => 'integer',
        'idEquipo' => 'integer',
        'idMantenimiento' => 'integer',
        'fechaMantenimiento' => 'date',
    ];

    // Relaciones
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'idAdmin', 'idAdmin');
    }

    public function equipo()
    {
        return $this->belongsTo(Equipo::class, 'idEquipo', 'idEquipo');
    }

    public function mantenimiento()
    {
        return $this->belongsTo(Mantenimiento::class, 'idMantenimiento', 'idMantenimiento');
    }
}
