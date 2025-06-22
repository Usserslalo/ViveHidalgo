<?php

namespace App\Http\Controllers\Api;

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     properties={
 *         @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *         @OA\Property(property="name", type="string", example="John Doe"),
 *         @OA\Property(property="email", type="string", format="email", example="user@example.com"),
 *         @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *         @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true),
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="Region",
 *     type="object",
 *     title="Region",
 *     properties={
 *         @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *         @OA\Property(property="name", type="string", example="Comarca Minera"),
 *         @OA\Property(property="description", type="string", example="Una descripción de la región."),
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="Categoria",
 *     type="object",
 *     title="Categoria",
 *     properties={
 *         @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *         @OA\Property(property="name", type="string", example="Pueblo Mágico"),
 *         @OA\Property(property="description", type="string", example="Una descripción de la categoría."),
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="Destino",
 *     type="object",
 *     title="Destino",
 *     properties={
 *         @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *         @OA\Property(property="name", type="string", example="Huasca de Ocampo"),
 *         @OA\Property(property="slug", type="string", readOnly=true, example="huasca-de-ocampo"),
 *         @OA\Property(property="short_description", type="string", example="El primer Pueblo Mágico de México."),
 *         @OA\Property(property="description", type="string", example="Texto largo con la descripción detallada del destino."),
 *         @OA\Property(property="status", type="string", example="published", enum={"draft", "pending_review", "published", "rejected"}),
 *         @OA\Property(property="address", type="string", example="Centro, Huasca de Ocampo, Hgo."),
 *         @OA\Property(property="latitude", type="number", format="float", example=20.2186),
 *         @OA\Property(property="longitude", type="number", format="float", example=-98.5714),
 *         @OA\Property(property="region", type="object", ref="#/components/schemas/Region"),
 *         @OA\Property(property="categorias", type="array", @OA\Items(ref="#/components/schemas/Categoria")),
 *         @OA\Property(property="user", type="object", ref="#/components/schemas/User", description="El proveedor del servicio"),
 *     }
 * )
 */
class SwaggerController extends BaseController
{
    // Este controlador no tiene métodos, solo se usa para las definiciones de Swagger.
} 