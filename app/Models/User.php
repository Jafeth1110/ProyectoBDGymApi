<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    //protected $table = 'usuario';
    public $timestamps = false;

    protected $primaryKey = 'idUsuario';
    public $incrementing = false; // si tu idUsuario no es numérico autoincremental
    protected $keyType = 'string'; // si es una cadena (como parece ser)


    protected $fillable = [
        'idUsuario',
        'nombre',
        'apellido',
        'cedula',
        'email',
        'rol',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
           // 'password' => 'hashed',
        ];
    }
}
