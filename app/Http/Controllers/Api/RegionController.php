<?php

namespace App\Http\Controllers\Api;

use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegionController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $regions = Region::withCount('destinos')->get();
        return $this->successResponse($regions, 'Regiones obtenidas exitosamente.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|unique:regions,name|max:255',
            'description' => 'nullable|string',
        ]);

        $region = Region::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'description' => $validated['description'],
        ]);

        return $this->successResponse($region, 'Región creada exitosamente.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Region $region)
    {
        return $this->successResponse($region->loadCount('destinos'), 'Región obtenida exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Region $region)
    {
        $validated = $request->validate([
            'name' => [
                'sometimes',
                'string',
                Rule::unique('regions')->ignore($region->id),
                'max:255'
            ],
            'description' => 'nullable|string',
        ]);

        $updateData = [];
        if (isset($validated['name'])) {
            $updateData['name'] = $validated['name'];
            $updateData['slug'] = Str::slug($validated['name']);
        }
        if (isset($validated['description'])) {
            $updateData['description'] = $validated['description'];
        }

        $region->update($updateData);

        return $this->successResponse($region, 'Región actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Region $region)
    {
        if ($region->destinos()->count() > 0) {
            return $this->errorResponse('No se puede eliminar la región porque tiene destinos asociados.', 409);
        }
        
        $region->delete();

        return $this->successResponse(null, 'Región eliminada exitosamente.');
    }
}
