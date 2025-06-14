<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mantenimiento extends Model
{
    use HasFactory;

    protected $table = 'mantenimiento';
    public $timestamps = false;

    protected $primaryKey = 'idMantenimiento';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'descripcion',
        'costo',
        'idAdmin',
    ];

    protected $casts = [
        'costo' => 'integer',
    ];
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'idAdmin');
    }
}
