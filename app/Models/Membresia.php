<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Membresia extends Model
{
    use HasFactory;

    protected $table = 'membresia';
    protected $primaryKey = 'idMembresia';
    public $timestamps = false;

    protected $fillable = [
        'idCliente',
        'nombre',
        'descripcion',
        'tipoMem',
        'precio',
        'descuento',
        'fechaVenc',
        'fechaInicio',
        'estado',
        'esPlantilla',
        'pagada',
        'fechaUltimoPago',
        'requierePago',
    ];

    protected $casts = [
        'fechaVenc' => 'date',
        'fechaInicio' => 'date',
        'fechaCreacion' => 'datetime',
        'fechaUltimoPago' => 'datetime',
        'idCliente' => 'integer',
        'precio' => 'decimal:2',
        'descuento' => 'decimal:2',
        'estado' => 'integer',
        'esPlantilla' => 'integer',
        'pagada' => 'boolean',
        'requierePago' => 'boolean',
    ];

    protected $appends = ['precio_formateado', 'precio_final', 'estado_texto', 'estado_pago'];

    // Reglas de validación para plantillas
    public static function rulesPlantilla()
    {
        return [
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'tipoMem' => 'required|string|in:Diaria,Semanal,Quincenal,Mensual,Trimestral,Semestral,Anual',
            'precio' => 'required|numeric|min:0',
            'descuento' => 'sometimes|numeric|min:0|max:100',
            'estado' => 'sometimes|integer|in:0,1|nullable',
        ];
    }

    // Reglas de validación para membresías de cliente
    public static function rulesCliente()
    {
        return [
            'idCliente' => 'required|integer|exists:cliente,idCliente',
            'nombre' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'tipoMem' => 'required|string|in:Diaria,Semanal,Quincenal,Mensual,Trimestral,Semestral,Anual',
            'precio' => 'required|numeric|min:0',
            'descuento' => 'sometimes|numeric|min:0|max:100',
            'fechaVenc' => 'required|date|after:fechaInicio',
            'fechaInicio' => 'required|date',
            'estado' => 'sometimes|integer|in:0,1|nullable',
        ];
    }

    // Reglas de validación para update
    public static function rulesUpdate($esPlantilla = false)
    {
        $rules = [
            'tipoMem' => 'required|string|in:Diaria,Semanal,Quincenal,Mensual,Trimestral,Semestral,Anual',
            'precio' => 'required|numeric|min:0',
            'descuento' => 'sometimes|numeric|min:0|max:100',
            'estado' => 'sometimes|integer|in:0,1|nullable',
            'esPlantilla' => 'sometimes|integer|in:0,1|nullable',
            'descripcion' => 'nullable|string',
            'nombre' => 'nullable|string|max:100'
        ];

        if (!$esPlantilla) {
            // Para membresías de cliente
            $rules['idCliente'] = 'required|integer|exists:cliente,idCliente';
            $rules['fechaInicio'] = 'required|date';
            $rules['fechaVenc'] = 'required|date|after:fechaInicio';
        } else {
            // Para plantillas
            $rules['idCliente'] = 'nullable|integer';
            $rules['fechaInicio'] = 'nullable|date';
            $rules['fechaVenc'] = 'nullable|date';
        }

        return $rules;
    }

    // Reglas de validación (mantener para compatibilidad)
    public static function rules($id = null)
    {
        return [
            'idCliente' => 'nullable|integer|exists:cliente,idCliente',
            'nombre' => 'nullable|string|max:100',
            'descripcion' => 'nullable|string',
            'tipoMem' => 'required|string|in:Diaria,Semanal,Quincenal,Mensual,Trimestral,Semestral,Anual',
            'precio' => 'required|numeric|min:0',
            'descuento' => 'sometimes|numeric|min:0|max:100',
            'fechaVenc' => 'nullable|date|after:fechaInicio',
            'fechaInicio' => 'nullable|date',
            'estado' => 'sometimes|integer|in:0,1',
            'esPlantilla' => 'sometimes|integer|in:0,1',
        ];
    }

    // Relación con cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'idCliente');
    }

    // Relación con pagos
    public function pagos()
    {
        return $this->hasMany(Pago::class, 'idMembresia');
    }

    // Método para verificar si está activa
    public function isActiva()
    {
        return $this->estado === 1;
    }

    // Método para verificar si está vencida
    public function isVencida()
    {
        return $this->fechaVenc && $this->fechaVenc < now()->toDateString();
    }

    // Método para verificar si es plantilla
    public function isPlantilla()
    {
        return $this->esPlantilla === 1;
    }

    // Scope para plantillas
    public function scopePlantillas($query)
    {
        return $query->where('esPlantilla', 1);
    }

    // Scope para membresías de clientes
    public function scopeClientes($query)
    {
        return $query->where('esPlantilla', 0);
    }

    // Scope para membresías activas
    public function scopeActivas($query)
    {
        return $query->where('estado', 1);
    }

    // Scope para membresías vencidas
    public function scopeVencidas($query)
    {
        return $query->where('fechaVenc', '<', now()->toDateString());
    }

    // Scope para membresías por cliente
    public function scopeByCliente($query, $idCliente)
    {
        return $query->where('idCliente', $idCliente)->where('esPlantilla', 0);
    }

    // Método para obtener días restantes
    public function diasRestantes()
    {
        if (!$this->fechaVenc || $this->fechaVenc < now()->toDateString()) {
            return 0; // Ya venció o es plantilla
        }
        
        return now()->diffInDays($this->fechaVenc);
    }

    // Método para verificar si vence pronto (dentro de 7 días)
    public function vencePronto()
    {
        return $this->diasRestantes() <= 7 && $this->diasRestantes() > 0;
    }

    // Método para calcular precio final con descuento
    public function calcularPrecioFinal()
    {
        if ($this->descuento > 0) {
            return round($this->precio * (1 - $this->descuento / 100.0), 2);
        }
        return $this->precio;
    }

    // Método para obtener el precio formateado
    public function getPrecioFormateadoAttribute()
    {
        return '₡' . number_format($this->precio, 2);
    }

    // Método para obtener el precio final calculado
    public function getPrecioFinalAttribute()
    {
        return $this->calcularPrecioFinal();
    }

    // Método para obtener el precio final formateado
    public function getPrecioFinalFormateadoAttribute()
    {
        return '₡' . number_format($this->calcularPrecioFinal(), 2);
    }

    // Método para obtener el estado como texto
    public function getEstadoTextoAttribute()
    {
        if ($this->esPlantilla) {
            return $this->estado ? 'Disponible' : 'No Disponible';
        }
        return $this->estado ? 'Activa' : 'Inactiva';
    }

    // Método para obtener el tipo de membresía
    public function getTipoTextoAttribute()
    {
        return $this->esPlantilla ? 'Plantilla' : 'Membresía Cliente';
    }

    // Método para verificar si está pagada
    public function isPagada()
    {
        return $this->pagada === true || $this->pagada === 1;
    }

    // Método para obtener el estado de pago
    public function getEstadoPagoAttribute()
    {
        if ($this->esPlantilla) {
            return 'No aplica';
        }
        
        if ($this->isPagada()) {
            return 'Pagada';
        }
        
        return 'Pendiente de pago';
    }

    // Scope para membresías pagadas
    public function scopePagadas($query)
    {
        return $query->where('pagada', 1);
    }

    // Scope para membresías pendientes de pago
    public function scopePendientesPago($query)
    {
        return $query->where('pagada', 0)->where('esPlantilla', 0);
    }

    // Método para verificar si requiere pago
    public function requierePago()
    {
        return $this->requierePago === true || $this->requierePago === 1;
    }
}