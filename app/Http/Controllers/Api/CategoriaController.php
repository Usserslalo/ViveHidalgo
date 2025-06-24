<?php

namespace App\Http\Controllers\Api;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoriaController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 15);
        $categorias = Categoria::withCount('destinos')->paginate($perPage);
        return $this->successResponse($categorias, 'Categorías obtenidas exitosamente.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:categorias,name|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
        ]);

        $categoria = Categoria::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
            'icon' => $validated['icon'],
        ]);

        return $this->successResponse($categoria, 'Categoría creada exitosamente.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Categoria $categoria)
    {
        return $this->successResponse($categoria->loadCount('destinos'), 'Categoría obtenida exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Categoria $categoria)
    {
        $validated = $request->validate([
            'name' => [
                'sometimes',
                'string',
                Rule::unique('categorias')->ignore($categoria->id),
                'max:255'
            ],
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:255',
        ]);

        $updateData = [];
        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
            $updateData['slug'] = Str::slug($validated['name']);
        }
        if (isset($validated['description'])) {
            $updateData['description'] = $validated['description'];
        }
        if (isset($validated['icon'])) {
            $updateData['icon'] = $validated['icon'];
        }

        $categoria->update($updateData);

        return $this->successResponse($categoria, 'Categoría actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Categoria $categoria)
    {
        // Opcional: verificar si la categoría tiene destinos asociados antes de borrar.
        if ($categoria->destinos()->count() > 0) {
            return $this->errorResponse('No se puede eliminar la categoría porque tiene destinos asociados.', 409);
        }

        $categoria->delete();

        return $this->successResponse(null, 'Categoría eliminada exitosamente.');
    }
}
