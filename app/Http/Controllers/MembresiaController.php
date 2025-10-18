<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Membresia;

class MembresiaController extends Controller
{
    /**
     * Función auxiliar para limpiar datos recursivamente
     */
    private function cleanData($data) {
        if (is_array($data)) {
            array_walk_recursive($data, function(&$value) {
                if (is_string($value)) {
                    $value = trim($value);
                }
            });
            return $data;
        }
        return is_string($data) ? trim($data) : $data;
    }

    public function index()
    {
        try {
            $membresias = \DB::select('EXEC pa_ObtenerMembresias');
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $membresias,
                'total' => count($membresias)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener las membresías: ' . $e->getMessage()
            ]);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $this->cleanData($request->input('data', $request->all()));

            $validator = Validator::make($data, [
                'idCliente' => 'required|integer',
                'nombre' => 'required|string|max:100',
                'descripcion' => 'nullable|string',
                'tipoMem' => 'required|string|in:Diaria,Semanal,Quincenal,Mensual,Trimestral,Semestral,Anual',
                'precio' => 'required|numeric|min:0',
                'descuento' => 'sometimes|numeric|min:0|max:100',
                'fechaVenc' => 'required|date|after:fechaInicio',
                'fechaInicio' => 'required|date',
                'estado' => 'sometimes|integer|in:0,1|nullable'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            // Manejar el estado para crear membresía
            $estadoParam = isset($data['estado']) ? (int)$data['estado'] : null;

            $result = \DB::select('EXEC pa_CrearMembresia ?, ?, ?, ?, ?, ?, ?, ?, ?', [
                $data['idCliente'],
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['tipoMem'],
                $data['precio'],
                $data['descuento'] ?? 0.00,
                $data['fechaVenc'],
                $data['fechaInicio'],
                $estadoParam
            ]);

            // El procedimiento siempre retorna un resultado con código y mensaje
            if (!empty($result) && isset($result[0]->codigo)) {
                $codigo = $result[0]->codigo;
                $mensaje = $result[0]->mensaje ?? 'Error desconocido';
                
                if ($codigo == 200) {
                    $response = [
                        'code' => 200,
                        'status' => 'success',
                        'message' => $mensaje
                    ];
                    
                    // Si hay ID de la nueva membresía, incluirlo
                    if (isset($result[0]->idMembresia)) {
                        $response['idMembresia'] = $result[0]->idMembresia;
                    }
                    
                    return response()->json($response);
                } else {
                    return response()->json([
                        'code' => $codigo,
                        'status' => 'error',
                        'message' => $mensaje
                    ]);
                }
            }

            // Fallback si no hay resultado (no debería pasar)
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error inesperado: el procedimiento no retornó resultado'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al crear la membresía: ' . $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        try {
            $membresia = \DB::select('EXEC pa_ObtenerMembresiaID ?', [$id]);
            
            if (empty($membresia)) {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'Membresía no encontrada'
                ]);
            }

            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $membresia[0]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener la membresía: ' . $e->getMessage()
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $data = $this->cleanData($request->input('data', $request->all()));

            // Determinar si es plantilla para validaciones dinámicas
            $esPlantilla = isset($data['esPlantilla']) && ($data['esPlantilla'] === true || $data['esPlantilla'] === 1);

            // Validaciones base
            $rules = [
                'tipoMem' => 'required|string|in:Diaria,Semanal,Quincenal,Mensual,Trimestral,Semestral,Anual',
                'precio' => 'required|numeric|min:0',
                'descuento' => 'sometimes|numeric|min:0|max:100',
                'estado' => 'sometimes|integer|in:0,1|nullable',
                'esPlantilla' => 'sometimes|integer|in:0,1|nullable',
                'descripcion' => 'nullable|string'
            ];

            // Validaciones condicionales basadas en si es plantilla
            if ($esPlantilla) {
                // Para plantillas: nombre siempre requerido
                $rules['nombre'] = 'required|string|max:100';
                $rules['idCliente'] = 'nullable|integer';
                $rules['fechaInicio'] = 'nullable|date';
                $rules['fechaVenc'] = 'nullable|date';
            } else {
                // Para membresías de cliente: requiere cliente y fechas, nombre requerido
                $rules['nombre'] = 'required|string|max:100';
                $rules['idCliente'] = 'required|integer|exists:cliente,idCliente';
                $rules['fechaInicio'] = 'required|date';
                $rules['fechaVenc'] = 'required|date|after:fechaInicio';
            }

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'status' => 'error',
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ]);
            }

            // Preparar parámetros para el procedimiento almacenado
            $estadoParam = isset($data['estado']) ? (int)$data['estado'] : null;
            
            $params = [
                $id,
                $esPlantilla ? null : ($data['idCliente'] ?? null),
                trim($data['nombre']),
                $data['descripcion'] ?? null,
                $data['tipoMem'],
                $data['precio'],
                $data['descuento'] ?? 0.00,
                $esPlantilla ? null : ($data['fechaVenc'] ?? null),
                $esPlantilla ? null : ($data['fechaInicio'] ?? null),
                $esPlantilla ? 1 : 0,
                $estadoParam
            ];

            $result = \DB::select('EXEC pa_ActualizarMembresia ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?', $params);

            // El procedimiento siempre retorna un resultado con código y mensaje
            if (!empty($result) && isset($result[0]->codigo)) {
                $codigo = $result[0]->codigo;
                $mensaje = $result[0]->mensaje ?? 'Error desconocido';
                
                if ($codigo == 200) {
                    return response()->json([
                        'code' => 200,
                        'status' => 'success',
                        'message' => $mensaje
                    ]);
                } else {
                    return response()->json([
                        'code' => $codigo,
                        'status' => 'error',
                        'message' => $mensaje
                    ]);
                }
            }

            // Fallback si no hay resultado (no debería pasar)
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error inesperado: el procedimiento no retornó resultado'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al actualizar la membresía: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Método helper para validar conversión entre plantilla y membresía de cliente
     */
    private function validateMembresiaData($data, $esPlantilla = false)
    {
        $errors = [];

        // Validaciones base
        if (empty($data['nombre']) || trim($data['nombre']) === '') {
            $errors[] = 'El nombre es requerido';
        }

        if (empty($data['tipoMem'])) {
            $errors[] = 'El tipo de membresía es requerido';
        }

        if (!isset($data['precio']) || $data['precio'] < 0) {
            $errors[] = 'El precio debe ser mayor o igual a 0';
        }

        if (isset($data['descuento']) && ($data['descuento'] < 0 || $data['descuento'] > 100)) {
            $errors[] = 'El descuento debe estar entre 0 y 100';
        }

        // Validaciones específicas para membresías de cliente
        if (!$esPlantilla) {
            if (empty($data['idCliente']) || $data['idCliente'] <= 0) {
                $errors[] = 'Debe seleccionar un cliente válido';
            }

            if (empty($data['fechaInicio'])) {
                $errors[] = 'La fecha de inicio es requerida';
            }

            if (empty($data['fechaVenc'])) {
                $errors[] = 'La fecha de vencimiento es requerida';
            }

            if (!empty($data['fechaInicio']) && !empty($data['fechaVenc'])) {
                $fechaInicio = new \DateTime($data['fechaInicio']);
                $fechaVenc = new \DateTime($data['fechaVenc']);
                
                if ($fechaVenc <= $fechaInicio) {
                    $errors[] = 'La fecha de vencimiento debe ser posterior a la fecha de inicio';
                }
            }
        }

        return $errors;
    }

    public function destroy($id)
    {
        try {
            $result = \DB::select('EXEC pa_BorrarMembresia ?', [$id]);

            // El procedimiento siempre retorna un resultado con código y mensaje
            if (!empty($result) && isset($result[0]->codigo)) {
                $codigo = $result[0]->codigo;
                $mensaje = $result[0]->mensaje ?? 'Error desconocido';
                
                if ($codigo == 200) {
                    return response()->json([
                        'code' => 200,
                        'status' => 'success',
                        'message' => $mensaje
                    ]);
                } else {
                    return response()->json([
                        'code' => $codigo,
                        'status' => 'error',
                        'message' => $mensaje
                    ]);
                }
            }

            // Fallback si no hay resultado (no debería pasar)
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error inesperado: el procedimiento no retornó resultado'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al eliminar la membresía: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar estados de todas las membresías basado en fechas de vencimiento
     */
    public function updateEstados()
    {
        try {
            \DB::statement('EXEC pa_ActualizarEstadosMembresias');
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'message' => 'Estados de membresías actualizados correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al actualizar estados de membresías: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener membresías activas
     */
    public function getActivas()
    {
        try {
            $membresias = \DB::select('
                SELECT 
                    m.idMembresia,
                    m.tipoMem,
                    m.precio,
                    m.fechaVenc,
                    m.fechaInicio,
                    m.estado,
                    c.idCliente,
                    u.nombre AS cliente_nombre,
                    u.apellido AS cliente_apellido,
                    u.email AS cliente_email,
                    c.fechaRegistro AS cliente_fechaRegistro
                FROM dbo.membresia m
                INNER JOIN cliente c ON m.idCliente = c.idCliente
                INNER JOIN users u ON c.idUsuario = u.idUsuario
                WHERE m.estado = 1
                ORDER BY m.fechaVenc ASC
            ');
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $membresias,
                'total' => count($membresias)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener membresías activas: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener membresías vencidas
     */
    public function getVencidas()
    {
        try {
            $membresias = \DB::select('
                SELECT 
                    m.idMembresia,
                    m.tipoMem,
                    m.precio,
                    m.fechaVenc,
                    m.fechaInicio,
                    m.estado,
                    c.idCliente,
                    u.nombre AS cliente_nombre,
                    u.apellido AS cliente_apellido,
                    u.email AS cliente_email,
                    c.fechaRegistro AS cliente_fechaRegistro
                FROM dbo.membresia m
                INNER JOIN cliente c ON m.idCliente = c.idCliente
                INNER JOIN users u ON c.idUsuario = u.idUsuario
                WHERE m.fechaVenc < CAST(GETDATE() AS DATE)
                ORDER BY m.fechaVenc DESC
            ');
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $membresias,
                'total' => count($membresias)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener membresías vencidas: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener membresías por cliente
     */
    public function getByCliente($idCliente)
    {
        try {
            $membresias = \DB::select('
                SELECT 
                    m.idMembresia,
                    m.tipoMem,
                    m.precio,
                    m.fechaVenc,
                    m.fechaInicio,
                    m.estado,
                    c.idCliente,
                    u.nombre AS cliente_nombre,
                    u.apellido AS cliente_apellido,
                    u.email AS cliente_email,
                    c.fechaRegistro AS cliente_fechaRegistro
                FROM dbo.membresia m
                INNER JOIN cliente c ON m.idCliente = c.idCliente
                INNER JOIN users u ON c.idUsuario = u.idUsuario
                WHERE m.idCliente = ?
                ORDER BY m.fechaInicio DESC
            ', [$idCliente]);
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $membresias,
                'total' => count($membresias)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener membresías del cliente: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Crear plantilla de membresía (solo admin)
     */
    public function createPlantilla(Request $request)
    {
        try {
            $data = $this->cleanData($request->input('data', $request->all()));

            $validator = Validator::make($data, [
                'nombre' => 'required|string|max:100',
                'descripcion' => 'nullable|string',
                'tipoMem' => 'required|string|in:Diaria,Semanal,Quincenal,Mensual,Trimestral,Semestral,Anual',
                'precio' => 'required|numeric|min:0',
                'descuento' => 'sometimes|numeric|min:0|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            $result = \DB::select('EXEC pa_CrearPlantillaMembresia ?, ?, ?, ?, ?', [
                $data['nombre'],
                $data['descripcion'] ?? null,
                $data['tipoMem'],
                $data['precio'],
                $data['descuento'] ?? 0.00
            ]);

            if (!empty($result) && isset($result[0]->codigo)) {
                $codigo = $result[0]->codigo;
                $mensaje = $result[0]->mensaje ?? 'Error desconocido';
                
                if ($codigo == 200) {
                    return response()->json([
                        'code' => 200,
                        'status' => 'success',
                        'message' => $mensaje
                    ]);
                } else {
                    return response()->json([
                        'code' => $codigo,
                        'status' => 'error',
                        'message' => $mensaje
                    ]);
                }
            }

            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error inesperado: el procedimiento no retornó resultado'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al crear la plantilla de membresía: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener plantillas de membresía disponibles (para clientes)
     */
    public function getPlantillas()
    {
        try {
            $plantillas = \DB::select('EXEC pa_ObtenerPlantillasMembresia');
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $plantillas,
                'total' => count($plantillas)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener plantillas de membresía: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener membresías de un cliente específico
     */
    public function getMembresiasCliente($idCliente)
    {
        try {
            $membresias = \DB::select('EXEC pa_ObtenerMembresiasCliente ?', [$idCliente]);
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $membresias,
                'total' => count($membresias)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener membresías del cliente: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Asignar una plantilla de membresía a un cliente
     */
    public function asignarMembresiaCliente(Request $request)
    {
        try {
            $data = $this->cleanData($request->input('data', $request->all()));

            $validator = Validator::make($data, [
                'idPlantilla' => 'required|integer',
                'idCliente' => 'required|integer',
                'fechaInicio' => 'required|date',
                'descuentoAdicional' => 'sometimes|numeric|min:0|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 400,
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ]);
            }

            $result = \DB::select('EXEC pa_AsignarMembresiaCliente ?, ?, ?, ?', [
                $data['idPlantilla'],
                $data['idCliente'],
                $data['fechaInicio'],
                $data['descuentoAdicional'] ?? 0.00
            ]);

            if (!empty($result) && isset($result[0]->codigo)) {
                $codigo = $result[0]->codigo;
                $mensaje = $result[0]->mensaje ?? 'Error desconocido';
                
                if ($codigo == 200) {
                    $response = [
                        'code' => 200,
                        'status' => 'success',
                        'message' => $mensaje
                    ];
                    
                    // Si hay ID de la nueva membresía, incluirlo
                    if (isset($result[0]->idMembresia)) {
                        $response['idMembresia'] = $result[0]->idMembresia;
                    }
                    
                    return response()->json($response);
                } else {
                    return response()->json([
                        'code' => $codigo,
                        'status' => 'error',
                        'message' => $mensaje
                    ]);
                }
            }

            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error inesperado: el procedimiento no retornó resultado'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al asignar membresía al cliente: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener estadísticas de membresías
     */
    public function getEstadisticas()
    {
        try {
            $estadisticas = \DB::select('EXEC pa_EstadisticasMembresias');
            
            return response()->json([
                'code' => 200,
                'status' => 'success',
                'data' => $estadisticas,
                'total' => count($estadisticas)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al obtener estadísticas de membresías: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Convertir plantilla a membresía de cliente
     */
    public function convertirPlantillaACliente(Request $request, $id)
    {
        try {
            $data = $this->cleanData($request->input('data', $request->all()));

            $validator = Validator::make($data, [
                'idCliente' => 'required|integer|exists:cliente,idCliente',
                'fechaInicio' => 'required|date',
                'fechaVenc' => 'required|date|after:fechaInicio',
                'descuentoAdicional' => 'sometimes|numeric|min:0|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'code' => 422,
                    'status' => 'error',
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ]);
            }

            // Obtener la plantilla
            $plantilla = \DB::select('EXEC pa_ObtenerMembresiaID ?', [$id]);
            
            if (empty($plantilla) || !$plantilla[0]->esPlantilla) {
                return response()->json([
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'Plantilla de membresía no encontrada'
                ]);
            }

            $plantillaData = $plantilla[0];

            // Calcular precio con descuento adicional si se proporciona
            $descuentoTotal = $plantillaData->descuento;
            if (isset($data['descuentoAdicional']) && $data['descuentoAdicional'] > 0) {
                $descuentoTotal += $data['descuentoAdicional'];
                if ($descuentoTotal > 100) {
                    $descuentoTotal = 100;
                }
            }

            // Actualizar la plantilla para convertirla en membresía de cliente
            $updateData = [
                'idCliente' => $data['idCliente'],
                'nombre' => $plantillaData->nombre,
                'descripcion' => $plantillaData->descripcion,
                'tipoMem' => $plantillaData->tipoMem,
                'precio' => $plantillaData->precio,
                'descuento' => $descuentoTotal,
                'fechaInicio' => $data['fechaInicio'],
                'fechaVenc' => $data['fechaVenc'],
                'esPlantilla' => false
            ];

            return $this->update(new Request(['data' => $updateData]), $id);

        } catch (\Exception $e) {
            return response()->json([
                'code' => 500,
                'status' => 'error',
                'message' => 'Error al convertir plantilla: ' . $e->getMessage()
            ]);
        }
    }
}