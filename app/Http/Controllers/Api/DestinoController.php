<?php

namespace App\Http\Controllers\Api;

use App\Models\Destino;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\DTOs\DestinoDTO;
use App\Services\DestinoService;
use Illuminate\Http\JsonResponse;

class DestinoController extends BaseController
{
    protected $destinoService;

    public function __construct(DestinoService $destinoService)
    {
        $this->destinoService = $destinoService;

        // Apply middleware to all methods.
        $this->middleware('auth:sanctum');

        // Apply policy to specific methods.
        $this->authorizeResource(Destino::class, 'destino');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Añadiremos filtros y paginación más adelante
        $destinos = Destino::with(['region', 'categorias', 'provider:id,name'])
            ->where('status', 'published')
            ->latest()
            ->paginate(10);

        return $this->successResponse($destinos, 'Destinos obtenidos exitosamente.');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();
        $request->merge(['user_id' => $user->id]);
        $validatedData = $request->validate(DestinoDTO::rules());
        $dto = new DestinoDTO($validatedData);
        
        $destino = $this->destinoService->create($dto);
        
        return $this->sendResponse($destino, 'Destino creado con éxito.', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Destino $destino)
    {
        // Solo mostrar si está publicado, o si el que lo ve es el dueño o un admin
        if ($destino->status !== 'published' && (!Auth::check() || (Auth::id() !== $destino->user_id && !Auth::user()->hasRole('admin')))) {
            return $this->errorResponse('Destino no encontrado.', 404);
        }

        return $this->successResponse($destino->load(['region', 'categorias', 'provider:id,name,email']), 'Destino obtenido exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Destino $destino): JsonResponse
    {
        $validatedData = $request->validate(DestinoDTO::rules($destino->id));

        $dto = new DestinoDTO($validatedData);

        $updatedDestino = $this->destinoService->update($destino, $dto);

        return $this->sendResponse($updatedDestino, 'Destino actualizado con éxito.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Destino $destino): JsonResponse
    {
        $this->destinoService->delete($destino);

        return $this->sendResponse(null, 'Destino eliminado con éxito.');
    }
}
