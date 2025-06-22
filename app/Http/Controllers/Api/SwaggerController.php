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
 *         @OA\Property(property="average_rating", type="number", format="float", example=4.5),
 *         @OA\Property(property="reviews_count", type="integer", example=25),
 *         @OA\Property(property="region", type="object", ref="#/components/schemas/Region"),
 *         @OA\Property(property="categorias", type="array", @OA\Items(ref="#/components/schemas/Categoria")),
 *         @OA\Property(property="user", type="object", ref="#/components/schemas/User", description="El proveedor del servicio"),
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="Review",
 *     type="object",
 *     title="Review",
 *     properties={
 *         @OA\Property(property="id", type="integer", readOnly=true, example=1),
 *         @OA\Property(property="user_id", type="integer", example=1),
 *         @OA\Property(property="destino_id", type="integer", example=1),
 *         @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5),
 *         @OA\Property(property="comment", type="string", nullable=true, example="Excelente destino turístico, muy recomendado."),
 *         @OA\Property(property="is_approved", type="boolean", example=false),
 *         @OA\Property(property="created_at", type="string", format="date-time", readOnly=true),
 *         @OA\Property(property="updated_at", type="string", format="date-time", readOnly=true),
 *         @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
 *         @OA\Property(property="destino", type="object", ref="#/components/schemas/Destino"),
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="ReviewCreateRequest",
 *     type="object",
 *     title="Review Create Request",
 *     required={"rating"},
 *     properties={
 *         @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5, description="Calificación del 1 al 5"),
 *         @OA\Property(property="comment", type="string", maxLength=1000, example="Excelente destino turístico, muy recomendado.", description="Comentario opcional"),
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="ReviewUpdateRequest",
 *     type="object",
 *     title="Review Update Request",
 *     properties={
 *         @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=4, description="Calificación del 1 al 5"),
 *         @OA\Property(property="comment", type="string", maxLength=1000, example="Comentario actualizado", description="Comentario opcional"),
 *     }
 * )
 */
class SwaggerController extends BaseController
{
    // Este controlador no tiene métodos, solo se usa para las definiciones de Swagger.
} 