<?php

namespace Tests\Feature\Api;

use App\Models\Caracteristica;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CaracteristicaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear el rol admin si no existe
        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        
        // Crear un usuario admin para las pruebas
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        Sanctum::actingAs($admin);
    }

    public function test_can_get_all_caracteristicas(): void
    {
        Caracteristica::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/caracteristicas');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'nombre',
                            'slug',
                            'tipo',
                            'icono',
                            'descripcion',
                            'activo',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'message'
                ]);
    }

    public function test_can_filter_caracteristicas_by_tipo(): void
    {
        Caracteristica::factory()->create(['tipo' => 'amenidad']);
        Caracteristica::factory()->create(['tipo' => 'actividad']);

        $response = $this->getJson('/api/v1/caracteristicas?tipo=amenidad');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('amenidad', $response->json('data.0.tipo'));
    }

    public function test_can_filter_caracteristicas_activas(): void
    {
        Caracteristica::factory()->create(['activo' => true]);
        Caracteristica::factory()->create(['activo' => false]);

        $response = $this->getJson('/api/v1/caracteristicas?activas=true');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertTrue($response->json('data.0.activo'));
    }

    public function test_can_create_caracteristica(): void
    {
        $caracteristicaData = [
            'nombre' => 'WiFi',
            'tipo' => 'amenidad',
            'icono' => 'fas fa-wifi',
            'descripcion' => 'Conexión inalámbrica a internet',
            'activo' => true
        ];

        $response = $this->postJson('/api/v1/caracteristicas', $caracteristicaData);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'nombre',
                        'slug',
                        'tipo',
                        'icono',
                        'descripcion',
                        'activo'
                    ],
                    'message'
                ]);

        $this->assertDatabaseHas('caracteristicas', [
            'nombre' => 'WiFi',
            'tipo' => 'amenidad'
        ]);
    }

    public function test_can_show_caracteristica(): void
    {
        $caracteristica = Caracteristica::factory()->create();

        $response = $this->getJson("/api/v1/caracteristicas/{$caracteristica->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'id' => $caracteristica->id,
                        'nombre' => $caracteristica->nombre
                    ]
                ]);
    }

    public function test_can_update_caracteristica(): void
    {
        $caracteristica = Caracteristica::factory()->create();
        $updateData = [
            'nombre' => 'WiFi Actualizado',
            'tipo' => 'amenidad',
            'activo' => false
        ];

        $response = $this->putJson("/api/v1/caracteristicas/{$caracteristica->id}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('caracteristicas', [
            'id' => $caracteristica->id,
            'nombre' => 'WiFi Actualizado',
            'activo' => false
        ]);
    }

    public function test_can_delete_caracteristica(): void
    {
        $caracteristica = Caracteristica::factory()->create();

        $response = $this->deleteJson("/api/v1/caracteristicas/{$caracteristica->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('caracteristicas', [
            'id' => $caracteristica->id
        ]);
    }

    public function test_can_get_caracteristicas_by_tipo_endpoint(): void
    {
        Caracteristica::factory()->create(['tipo' => 'amenidad']);
        Caracteristica::factory()->create(['tipo' => 'actividad']);

        $response = $this->getJson('/api/v1/caracteristicas/tipo/amenidad');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('amenidad', $response->json('data.0.tipo'));
    }

    public function test_can_get_caracteristicas_activas_endpoint(): void
    {
        Caracteristica::factory()->create(['activo' => true]);
        Caracteristica::factory()->create(['activo' => false]);

        $response = $this->getJson('/api/v1/caracteristicas/activas');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertTrue($response->json('data.0.activo'));
    }

    public function test_validates_required_fields_on_create(): void
    {
        $response = $this->postJson('/api/v1/caracteristicas', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['nombre', 'tipo']);
    }

    public function test_validates_tipo_values(): void
    {
        $response = $this->postJson('/api/v1/caracteristicas', [
            'nombre' => 'Test',
            'tipo' => 'invalid_tipo'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['tipo']);
    }

    public function test_auto_generates_slug_from_nombre(): void
    {
        $caracteristicaData = [
            'nombre' => 'WiFi Gratis',
            'tipo' => 'amenidad'
        ];

        $response = $this->postJson('/api/v1/caracteristicas', $caracteristicaData);

        $response->assertStatus(201);
        $this->assertDatabaseHas('caracteristicas', [
            'nombre' => 'WiFi Gratis',
            'slug' => 'wifi-gratis'
        ]);
    }
} 