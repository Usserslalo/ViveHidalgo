<?php

namespace Tests\Feature\Api;

use App\Models\Destino;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_search_for_destinos_and_regions()
    {
        // Arrange
        $region = Region::factory()->create(['name' => 'Comarca Minera Test']);
        Destino::factory()->create(['name' => 'Huasca de Ocampo', 'region_id' => $region->id, 'status' => 'published', 'short_description' => 'Pueblo Magico']);
        Destino::factory()->create(['name' => 'Real del Monte', 'region_id' => $region->id, 'status' => 'published', 'short_description' => 'Pueblo Magico y Paste']);
        Destino::factory()->create(['name' => 'Mineral del Chico', 'status' => 'published']);

        // Act
        $response = $this->getJson('/api/v1/search?query=Huasca');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.destinos')
            ->assertJsonCount(0, 'data.regiones')
            ->assertJsonFragment(['name' => 'Huasca de Ocampo']);

        // Crea una región separada para el destino para evitar la creación automática de una región que pueda coincidir
        $otraRegion = Region::factory()->create(['name' => 'Región Lejana']);

        // Creamos un destino de prueba con un slug que coincida con la búsqueda "minera"
        // El slug del destino es mineral-del-chico
        Destino::factory()->create([
            'name' => 'Mineral del Chico',
            'region_id' => $otraRegion->id
        ]);

        // ESTE ES EL BLOQUE CORRECTO
        $response = $this->getJson('/api/v1/search?query=minera');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.regiones')
            ->assertJsonFragment(['name' => 'Comarca Minera Test']);

        // Verificar que encontramos al menos el destino "Mineral del Chico"
        $responseData = $response->json('data');
        $this->assertContains('Mineral del Chico', collect($responseData['destinos'])->pluck('name')->toArray());
        
        // Verificar que encontramos al menos 1 destino (puede ser más de 1 si hay otros que coincidan)
        $this->assertGreaterThanOrEqual(1, count($responseData['destinos']));
    }

    /** @test */
    public function it_returns_empty_results_for_no_matches()
    {
        // Arrange
        Region::factory()->create(['name' => 'Sierra Gorda Test']);
        Destino::factory()->create(['name' => 'Zimapan Aventura', 'status' => 'published']);

        // Act
        $response = $this->getJson('/api/v1/search?query=Tula');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(0, 'data.destinos')
            ->assertJsonCount(0, 'data.regiones');
    }

    /** @test */
    public function it_only_returns_published_destinos_in_search()
    {
        // Arrange
        Destino::factory()->create(['name' => 'Destino Publicado', 'status' => 'published']);
        Destino::factory()->create(['name' => 'Destino Borrador', 'status' => 'draft']);
        Destino::factory()->create(['name' => 'Destino Pendiente', 'status' => 'pending_review']);

        // Act
        $response = $this->getJson('/api/v1/search?query=Destino');

        // Assert
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.destinos')
            ->assertJsonFragment(['name' => 'Destino Publicado'])
            ->assertJsonMissing(['name' => 'Destino Borrador'])
            ->assertJsonMissing(['name' => 'Destino Pendiente']);
    }

    /** @test */
    public function it_returns_a_validation_error_if_query_is_missing_or_too_short()
    {
        // Missing query
        $response = $this->getJson('/api/v1/search');
        $response->assertStatus(422)
            ->assertJsonValidationErrors('query');

        // Query too short
        $response = $this->getJson('/api/v1/search?query=a');
        $response->assertStatus(422)
            ->assertJsonValidationErrors('query');
    }
} 