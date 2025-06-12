<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Admin extends Model
{
    protected $table = 'admin';
    protected $primaryKey = 'idAdmin';
    public $timestamps = false;

    protected $fillable = [
        'idUsuario'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'idUsuario');
    }
}
