<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Destino;
use App\Models\Region;
use App\Models\Categoria;
use App\Models\Caracteristica;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * @OA\Tag(
 *     name="Provider Destinos",
 *     description="Endpoints para gestión de destinos por proveedores"
 * )
 */
class ProviderDestinoController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/v1/provider/destinos",
     *     operationId="getProviderDestinos",
     *     tags={"Provider Destinos"},
     *     summary="Listar destinos del proveedor",
     *     description="Obtiene la lista paginada de destinos del proveedor autenticado",
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
     *         @OA\Schema(type="string", enum={"published", "draft", "pending_review"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Buscar por nombre",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de destinos obtenida exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="current_page", type="integer"),
     *                 @OA\Property(property="data", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Balneario El Tephé"),
     *                     @OA\Property(property="slug", type="string", example="balneario-el-tephe"),
     *                     @OA\Property(property="status", type="string", example="published"),
     *                     @OA\Property(property="short_description", type="string"),
     *                     @OA\Property(property="region", type="object",
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     ),
     *                     @OA\Property(property="categorias", type="array", @OA\Items(
     *                         @OA\Property(property="id", type="integer"),
     *                         @OA\Property(property="name", type="string")
     *                     )),
     *                     @OA\Property(property="created_at", type="string", format="date-time"),
     *                     @OA\Property(property="updated_at", type="string", format="date-time")
     *                 )),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="per_page", type="integer")
     *             ),
     *             @OA\Property(property="message", type="string", example="Destinos del proveedor obtenidos exitosamente.")
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
            'status' => 'nullable|in:published,draft,pending_review',
            'search' => 'nullable|string|max:100',
        ]);

        $perPage = min($request->input('per_page', 15), 50);
        
        $query = Destino::with(['region:id,name', 'categorias:id,name'])
            ->where('user_id', $user->id);

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filtro por búsqueda
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        $destinos = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return $this->successResponse($destinos, 'Destinos del proveedor obtenidos exitosamente.');
    }

    /**
     * @OA\Post(
     *     path="/api/v1/provider/destinos",
     *     operationId="createProviderDestino",
     *     tags={"Provider Destinos"},
     *     summary="Crear nuevo destino",
     *     description="Crea un nuevo destino para el proveedor autenticado",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name", "region_id", "short_description", "description"},
     *             @OA\Property(property="name", type="string", example="Balneario El Tephé"),
     *             @OA\Property(property="region_id", type="integer", example=1),
     *             @OA\Property(property="short_description", type="string", example="Descripción corta del destino"),
     *             @OA\Property(property="description", type="string", example="Descripción completa del destino"),
     *             @OA\Property(property="address", type="string", example="Carretera 123"),
     *             @OA\Property(property="latitude", type="number", format="float", example=20.123456),
     *             @OA\Property(property="longitude", type="number", format="float", example=-98.123456),
     *             @OA\Property(property="phone", type="string", example="+52 123 456 7890"),
     *             @OA\Property(property="whatsapp", type="string", example="+52 123 456 7890"),
     *             @OA\Property(property="email", type="string", format="email", example="contacto@destino.com"),
     *             @OA\Property(property="website", type="string", format="url", example="https://destino.com"),
     *             @OA\Property(property="status", type="string", enum={"draft", "pending_review"}, default="draft"),
     *             @OA\Property(property="categoria_ids", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="caracteristica_ids", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="tag_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Destino creado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Balneario El Tephé"),
     *                 @OA\Property(property="slug", type="string", example="balneario-el-tephe"),
     *                 @OA\Property(property="status", type="string", example="draft")
     *             ),
     *             @OA\Property(property="message", type="string", example="Destino creado exitosamente.")
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
            'name' => 'required|string|max:255',
            'region_id' => 'required|integer|exists:regions,id',
            'short_description' => 'required|string|max:500',
            'description' => 'required|string|max:5000',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'status' => 'nullable|in:draft,pending_review',
            'categoria_ids' => 'nullable|array',
            'categoria_ids.*' => 'integer|exists:categorias,id',
            'caracteristica_ids' => 'nullable|array',
            'caracteristica_ids.*' => 'integer|exists:caracteristicas,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ]);

        try {
            DB::beginTransaction();

            // Generar slug único
            $slug = Str::slug($validated['name']);
            $originalSlug = $slug;
            $counter = 1;
            
            while (Destino::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // Crear destino
            $destino = Destino::create([
                'user_id' => $user->id,
                'region_id' => $validated['region_id'],
                'name' => $validated['name'],
                'slug' => $slug,
                'short_description' => $validated['short_description'],
                'description' => $validated['description'],
                'address' => $validated['address'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'whatsapp' => $validated['whatsapp'] ?? null,
                'email' => $validated['email'] ?? null,
                'website' => $validated['website'] ?? null,
                'status' => $validated['status'] ?? 'draft',
            ]);

            // Asociar categorías
            if (!empty($validated['categoria_ids'])) {
                $destino->categorias()->attach($validated['categoria_ids']);
            }

            // Asociar características
            if (!empty($validated['caracteristica_ids'])) {
                $destino->caracteristicas()->attach($validated['caracteristica_ids']);
            }

            // Asociar tags
            if (!empty($validated['tag_ids'])) {
                $destino->tags()->attach($validated['tag_ids']);
            }

            DB::commit();

            $destino->load(['region:id,name', 'categorias:id,name']);

            return $this->successResponse([
                'id' => $destino->id,
                'name' => $destino->name,
                'slug' => $destino->slug,
                'status' => $destino->status,
            ], 'Destino creado exitosamente.', 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al crear el destino: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/provider/destinos/{id}",
     *     operationId="getProviderDestino",
     *     tags={"Provider Destinos"},
     *     summary="Obtener destino específico",
     *     description="Obtiene los detalles de un destino específico del proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del destino",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Destino obtenido exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="name", type="string"),
     *                 @OA\Property(property="slug", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="short_description", type="string"),
     *                 @OA\Property(property="description", type="string"),
     *                 @OA\Property(property="address", type="string"),
     *                 @OA\Property(property="latitude", type="number"),
     *                 @OA\Property(property="longitude", type="number"),
     *                 @OA\Property(property="phone", type="string"),
     *                 @OA\Property(property="whatsapp", type="string"),
     *                 @OA\Property(property="email", type="string"),
     *                 @OA\Property(property="website", type="string"),
     *                 @OA\Property(property="region", type="object",
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 ),
     *                 @OA\Property(property="categorias", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 )),
     *                 @OA\Property(property="caracteristicas", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="nombre", type="string")
     *                 )),
     *                 @OA\Property(property="tags", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer"),
     *                     @OA\Property(property="name", type="string")
     *                 )),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(property="message", type="string", example="Destino obtenido exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destino no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Destino no encontrado.")
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

        // Buscar destino del proveedor
        $destino = Destino::with([
            'region:id,name',
            'categorias:id,name',
            'caracteristicas:id,nombre',
            'tags:id,name'
        ])
        ->where('id', $id)
        ->where('user_id', $user->id)
        ->first();

        if (!$destino) {
            return $this->errorResponse('Destino no encontrado.', 404);
        }

        return $this->successResponse($destino, 'Destino obtenido exitosamente.');
    }

    /**
     * @OA\Put(
     *     path="/api/v1/provider/destinos/{id}",
     *     operationId="updateProviderDestino",
     *     tags={"Provider Destinos"},
     *     summary="Actualizar destino",
     *     description="Actualiza un destino existente del proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del destino",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Balneario El Tephé"),
     *             @OA\Property(property="region_id", type="integer", example=1),
     *             @OA\Property(property="short_description", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="address", type="string"),
     *             @OA\Property(property="latitude", type="number", format="float"),
     *             @OA\Property(property="longitude", type="number", format="float"),
     *             @OA\Property(property="phone", type="string"),
     *             @OA\Property(property="whatsapp", type="string"),
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="website", type="string", format="url"),
     *             @OA\Property(property="status", type="string", enum={"draft", "pending_review", "published"}),
     *             @OA\Property(property="categoria_ids", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="caracteristica_ids", type="array", @OA\Items(type="integer")),
     *             @OA\Property(property="tag_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Destino actualizado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Destino actualizado exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destino no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Destino no encontrado.")
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

        // Buscar destino del proveedor
        $destino = Destino::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$destino) {
            return $this->errorResponse('Destino no encontrado.', 404);
        }

        // Validar datos
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'region_id' => 'sometimes|required|integer|exists:regions,id',
            'short_description' => 'sometimes|required|string|max:500',
            'description' => 'sometimes|required|string|max:5000',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'phone' => 'nullable|string|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'status' => 'sometimes|in:draft,pending_review,published',
            'categoria_ids' => 'nullable|array',
            'categoria_ids.*' => 'integer|exists:categorias,id',
            'caracteristica_ids' => 'nullable|array',
            'caracteristica_ids.*' => 'integer|exists:caracteristicas,id',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ]);

        try {
            DB::beginTransaction();

            // Generar nuevo slug si cambió el nombre
            if (isset($validated['name']) && $validated['name'] !== $destino->name) {
                $slug = Str::slug($validated['name']);
                $originalSlug = $slug;
                $counter = 1;
                
                while (Destino::where('slug', $slug)->where('id', '!=', $destino->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                
                $validated['slug'] = $slug;
            }

            // Actualizar destino
            $destino->update($validated);

            // Actualizar relaciones
            if (isset($validated['categoria_ids'])) {
                $destino->categorias()->sync($validated['categoria_ids']);
            }

            if (isset($validated['caracteristica_ids'])) {
                $destino->caracteristicas()->sync($validated['caracteristica_ids']);
            }

            if (isset($validated['tag_ids'])) {
                $destino->tags()->sync($validated['tag_ids']);
            }

            DB::commit();

            $destino->load(['region:id,name', 'categorias:id,name', 'caracteristicas:id,nombre', 'tags:id,name']);

            return $this->successResponse($destino, 'Destino actualizado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al actualizar el destino: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/v1/provider/destinos/{id}",
     *     operationId="deleteProviderDestino",
     *     tags={"Provider Destinos"},
     *     summary="Eliminar destino",
     *     description="Elimina un destino del proveedor",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID del destino",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Destino eliminado exitosamente",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Destino eliminado exitosamente.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Destino no encontrado",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Destino no encontrado.")
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

        // Buscar destino del proveedor
        $destino = Destino::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$destino) {
            return $this->errorResponse('Destino no encontrado.', 404);
        }

        try {
            DB::beginTransaction();

            // Eliminar relaciones
            $destino->categorias()->detach();
            $destino->caracteristicas()->detach();
            $destino->tags()->detach();

            // Eliminar destino
            $destino->delete();

            DB::commit();

            return $this->successResponse(null, 'Destino eliminado exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Error al eliminar el destino: ' . $e->getMessage(), 500);
        }
    }
} 