<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Promocion;
use App\Models\Destino;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * @OA\Tag(
 *     name="Provider Promociones",
 *     description="Endpoints para gestión de promociones por proveedores"
 * )
 */
class ProviderPromocionController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/provider/promociones",
     *     operationId="getProviderPromociones",
     *     tags={"Provider Promociones"},
     *     summary="Listar promociones del proveedor",
     *     description="Obtiene la lista paginada de promociones del proveedor autenticado",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Número de página",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=15, maximum=50)
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="Filtrar por estado",
     *         required=false,
     *         @OA\Schema(type="string", enum={"active", "inactive", "expired"})
     *     ),
     *     @OA\Parameter(
     *         name="destino_id",
     *         in="query",
     *         description="Filtrar por destino específico",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de promociones obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="title", type="string", example="Descuento de Verano"),
     *                     @OA\Property(property="description", type="string"),
     *                     @OA\Property(property="discount_percentage", type="integer", example=20),
     *                     @OA\Property(property="code", type="string", example="VERANO20"),
     *                     @OA\Property(property="start_date", type="string", format="date"),
     *                     @OA\Property(property="end_date", type="string", format="date"),
     *                     @OA\Property(property="is_active", type="boolean", example=true),
     *                     @OA\Property(property="destino", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     ),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             ),
     *             @OA\Property(property="message", type="string", example="Promociones del proveedor obtenidas exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="No autorizado.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Acceso denegado - Solo para proveedores",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Acceso denegado. Solo para proveedores.")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        // Verificar que el usuario sea un proveedor
        $user = $request->user();
        if (!$user->isProvider()) {
            return $this->errorResponse('Acceso denegado. Solo para proveedores.', 403);
        }

        // Validar parámetros
        $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50',
            'status' => 'nullable|in:active,inactive,expired',
            'destino_id' => 'nullable|integer|exists:destinos,id',
        ]);

        $perPage = min($request->input('per_page', 15), 50);
        $now = Carbon::now();
        
        $query = Promocion::with(['destino:id,name'])
            ->whereHas('destino', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

        // Filtro por estado
        if ($request->filled('status')) {
            switch ($request->input('status')) {
                case 'active':
                    $query->where('is_active', true)
                          ->where('start_date', '<=', $now)
                          ->where('end_date', '>=', $now);
                    break;
                case 'inactive':
                    $query->where('is_active', false);
                    break;
                case 'expired':
                    $query->where('end_date', '<', $now);
                    break;
            }
        }

        // Filtro por destino
        if ($request->filled('destino_id')) {
            $query->where('destino_id', $request->input('destino_id'));
        }

        $promociones = $query->orderBy('created_at', 'desc')->paginate($perPage);

        // Agregar información adicional
        $promociones->getCollection()->transform(function ($promocion) use ($now) {
            $promocion->dias_restantes = $now->diffInDays($promocion->end_date, false);
            $promocion->is_expired = $promocion->end_date < $now;
            return $promocion;
        });

        return $this->successResponse($promociones, 'Promociones del proveedor obtenidas exitosamente.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/provider/promociones",
     *     operationId="createProviderPromocion",
     *     tags={"Provider Promociones"},
     *     summary="Crear nueva promoción",
     *     description="Crea una nueva promoción para un destino del proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "destino_id", "discount_percentage", "start_date", "end_date"},
     *             @OA\Property(property="title", type="string", example="Descuento de Verano"),
     *             @OA\Property(property="description", type="string", example="Descripción de la promoción"),
     *             @OA\Property(property="destino_id", type="integer", example=1),
     *             @OA\Property(property="discount_percentage", type="integer", example=20, minimum=1, maximum=100),
     *             @OA\Property(property="code", type="string", example="VERANO20"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2024-06-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2024-08-31"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Promoción creada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="title", type="string", example="Descuento de Verano"),
     *                 @OA\Property(property="discount_percentage", type="integer", example=20),
     *                 @OA\Property(property="start_date", type="string", format="date"),
     *                 @OA\Property(property="end_date", type="string", format="date")
     *             ),
     *             @OA\Property(property="message", type="string", example="Promoción creada exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Los datos proporcionados no son válidos."),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        // Verificar que el usuario sea un proveedor
        $user = $request->user();
        if (!$user->isProvider()) {
            return $this->errorResponse('Acceso denegado. Solo para proveedores.', 403);
        }

        // Validar datos
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'destino_id' => 'required|integer|exists:destinos,id',
            'discount_percentage' => 'required|integer|min:1|max:100',
            'code' => 'nullable|string|max:50|unique:promocions,code',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        // Verificar que el destino pertenezca al proveedor
        $destino = Destino::where('id', $validated['destino_id'])
            ->where('user_id', $user->id)
            ->first();

        if (!$destino) {
            return $this->errorResponse('El destino especificado no pertenece a este proveedor.', 422);
        }

        try {
            DB::beginTransaction();

            // Crear promoción
            $promocion = Promocion::create([
                'destino_id' => $validated['destino_id'],
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'discount_percentage' => $validated['discount_percentage'],
                'code' => $validated['code'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'is_active' => $validated['is_active'] ?? true,
            ]);

            DB::commit();

            $promocion->load('destino:id,name');

            return $this->successResponse([
                'id' => $promocion->id,
                'title' => $promocion->title,
                'discount_percentage' => $promocion->discount_percentage,
                'start_date' => $promocion->start_date,
                'end_date' => $promocion->end_date,
                'destino' => $promocion->destino,
            ], 'Promoción creada exitosamente.', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al crear la promoción: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/provider/promociones/{id}",
     *     operationId="getProviderPromocion",
     *     tags={"Provider Promociones"},
     *     summary="Obtener promoción específica",
     *     description="Obtiene los detalles de una promoción específica del proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la promoción",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Promoción obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="title", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="discount_percentage", type="integer"),
     *                 @OA\Property(property="code", type="string"),
     *                 @OA\Property(property="start_date", type="string", format="date"),
     *                 @OA\Property(property="end_date", type="string", format="date"),
     *                 @OA\Property(property="is_active", type="boolean"),
     *                 @OA\Property(property="destino", type="object"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="message", type="string", example="Promoción obtenida exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Promoción no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Promoción no encontrada.")
     *         )
     *     )
     * )
     */
    public function show(Request $request, int $id): JsonResponse
    {
        // Verificar que el usuario sea un proveedor
        $user = $request->user();
        if (!$user->isProvider()) {
            return $this->errorResponse('Acceso denegado. Solo para proveedores.', 403);
        }

        // Buscar promoción del proveedor
        $promocion = Promocion::with(['destino:id,name'])
            ->whereHas('destino', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('id', $id)
            ->first();

        if (!$promocion) {
            return $this->errorResponse('Promoción no encontrada.', 404);
        }

        // Agregar información adicional
        $now = Carbon::now();
        $promocion->dias_restantes = $now->diffInDays($promocion->end_date, false);
        $promocion->is_expired = $promocion->end_date < $now;

        return $this->successResponse($promocion, 'Promoción obtenida exitosamente.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/provider/promociones/{id}",
     *     operationId="updateProviderPromocion",
     *     tags={"Provider Promociones"},
     *     summary="Actualizar promoción",
     *     description="Actualiza una promoción existente del proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la promoción",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Descuento de Verano"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="destino_id", type="integer", example=1),
     *             @OA\Property(property="discount_percentage", type="integer", example=20, minimum=1, maximum=100),
     *             @OA\Property(property="code", type="string", example="VERANO20"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="is_active", type="boolean")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Promoción actualizada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Promoción actualizada exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Promoción no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Promoción no encontrada.")
     *         )
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        // Verificar que el usuario sea un proveedor
        $user = $request->user();
        if (!$user->isProvider()) {
            return $this->errorResponse('Acceso denegado. Solo para proveedores.', 403);
        }

        // Buscar promoción del proveedor
        $promocion = Promocion::whereHas('destino', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('id', $id)
        ->first();

        if (!$promocion) {
            return $this->errorResponse('Promoción no encontrada.', 404);
        }

        // Validar datos
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'destino_id' => 'sometimes|required|integer|exists:destinos,id',
            'discount_percentage' => 'sometimes|required|integer|min:1|max:100',
            'code' => 'nullable|string|max:50|unique:promocions,code,' . $id,
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        // Verificar que el destino pertenezca al proveedor si se está cambiando
        if (isset($validated['destino_id']) && $validated['destino_id'] !== $promocion->destino_id) {
            $destino = Destino::where('id', $validated['destino_id'])
                ->where('user_id', $user->id)
                ->first();

            if (!$destino) {
                return $this->errorResponse('El destino especificado no pertenece a este proveedor.', 422);
            }
        }

        try {
            DB::beginTransaction();

            // Actualizar promoción
            $promocion->update($validated);

            DB::commit();

            $promocion->load('destino:id,name');

            return $this->successResponse($promocion, 'Promoción actualizada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al actualizar la promoción: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/provider/promociones/{id}",
     *     operationId="deleteProviderPromocion",
     *     tags={"Provider Promociones"},
     *     summary="Eliminar promoción",
     *     description="Elimina una promoción del proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la promoción",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Promoción eliminada exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Promoción eliminada exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Promoción no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Promoción no encontrada.")
     *         )
     *     )
     * )
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        // Verificar que el usuario sea un proveedor
        $user = $request->user();
        if (!$user->isProvider()) {
            return $this->errorResponse('Acceso denegado. Solo para proveedores.', 403);
        }

        // Buscar promoción del proveedor
        $promocion = Promocion::whereHas('destino', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })
        ->where('id', $id)
        ->first();

        if (!$promocion) {
            return $this->errorResponse('Promoción no encontrada.', 404);
        }

        try {
            DB::beginTransaction();

            // Eliminar promoción
            $promocion->delete();

            DB::commit();

            return $this->successResponse(null, 'Promoción eliminada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al eliminar la promoción: ' . $e->getMessage(), 500);
        }
    }
} 