<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Models\Caracteristica;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\Rule;

class CaracteristicaController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Caracteristica::query();

        // Filtrar por tipo si se especifica
        if ($request->has('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        // Filtrar solo activas si se especifica
        if ($request->boolean('activas')) {
            $query->activas();
        }

        // Ordenar por nombre
        $query->orderBy('nombre');

        $perPage = $request->get('per_page', 15);
        $caracteristicas = $query->paginate($perPage);

        return $this->sendResponse($caracteristicas, 'Características recuperadas con éxito.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:caracteristicas,slug',
            'tipo' => 'required|string|in:amenidad,actividad,cultural,natural,especial,alojamiento,general',
            'icono' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $caracteristica = Caracteristica::create($validated);

        return $this->sendResponse($caracteristica, 'Característica creada con éxito.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Caracteristica $caracteristica): JsonResponse
    {
        return $this->sendResponse($caracteristica, 'Característica recuperada con éxito.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Caracteristica $caracteristica): JsonResponse
    {
        $validated = $request->validate([
            'nombre' => 'sometimes|required|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('caracteristicas', 'slug')->ignore($caracteristica->id),
            ],
            'tipo' => 'sometimes|required|string|in:amenidad,actividad,cultural,natural,especial,alojamiento,general',
            'icono' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
            'activo' => 'boolean',
        ]);

        $caracteristica->update($validated);

        return $this->sendResponse($caracteristica, 'Característica actualizada con éxito.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Caracteristica $caracteristica): JsonResponse
    {
        // Verificar si hay destinos asociados
        if ($caracteristica->destinos()->count() > 0) {
            return $this->sendError('No se puede eliminar la característica porque tiene destinos asociados.', [], 422);
        }

        $caracteristica->delete();

        return $this->sendResponse(null, 'Característica eliminada con éxito.');
    }

    /**
     * Obtener características por tipo
     */
    public function porTipo(string $tipo): JsonResponse
    {
        $caracteristicas = Caracteristica::porTipo($tipo)->activas()->orderBy('nombre')->get();

        return $this->sendResponse($caracteristicas, "Características de tipo '{$tipo}' recuperadas con éxito.");
    }

    /**
     * Obtener todas las características activas
     */
    public function activas(): JsonResponse
    {
        $caracteristicas = Caracteristica::activas()->orderBy('nombre')->get();

        return $this->sendResponse($caracteristicas, 'Características activas recuperadas con éxito.');
    }
} 