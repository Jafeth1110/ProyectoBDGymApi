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
    public $incrementing = true; // Cambiado a true porque es auto-incremental
    protected $keyType = 'int'; // Cambiado a int

    protected $appends = ['rol']; // Esto asegura que 'rol' esté disponible automáticamente

    protected $fillable = [
        'nombre',
        'apellido',
        'cedula',
        'email',
        'idRol', // Cambiado de 'rol' a 'idRol'
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

    // Relaciones
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'idRol');
    }

    public function admin()
    {
        return $this->hasOne(Admin::class, 'idUsuario');
    }

    public function cliente()
    {
        return $this->hasOne(Cliente::class, 'idUsuario');
    }

    public function entrenador()
    {
        return $this->hasOne(Entrenador::class, 'idUsuario');
    }

    public function telefonos()
    {
        return $this->hasMany(Telefono::class, 'idUsuario', 'idUsuario');
    }

    // Métodos útiles para mantener compatibilidad
    public function getRolAttribute()
    {
        // Retornar rol basado en idRol sin cargar relaciones
        switch ($this->idRol) {
            case 1:
                return 'admin';
            case 2:
                return 'cliente';
            case 3:
                return 'entrenador';
            default:
                return null;
        }
    }

    public function isAdmin()
    {
        return $this->idRol === 1;
    }

    public function isCliente()
    {
        return $this->idRol === 2;
    }

    public function isEntrenador()
    {
        return $this->idRol === 3;
    }
}
