<?php

namespace Tests\Feature\Api\Public;

use App\Models\PromocionDestacada;
use App\Models\Destino;
use App\Models\User;
use App\Models\Region;
use App\Models\Categoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PromocionDestacadaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear roles y permisos básicos para el test
        $this->createRolesAndPermissions();
    }

    private function createRolesAndPermissions(): void
    {
        // Crear permisos básicos
        $permissions = [
            'view-destinos',
            'create-destinos',
            'edit-destinos',
            'delete-destinos',
            'view-promociones',
            'create-promociones',
            'edit-promociones',
            'delete-promociones',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Crear rol provider
        $providerRole = Role::firstOrCreate(['name' => 'provider', 'guard_name' => 'web']);
        $providerRole->givePermissionTo($permissions);
    }

    #[Test]
    public function it_can_get_vigentes_promociones_destacadas()
    {
        // Limpiar promociones existentes para este test
        PromocionDestacada::query()->delete();

        // Crear datos de prueba directamente sin usar seeders
        $user = User::factory()->create();
        $user->assignRole('provider');

        $region = Region::create([
            'name' => 'Test Region',
            'description' => 'Test Region Description',
            'slug' => 'test-region',
        ]);

        $categoria = Categoria::create([
            'name' => 'Test Category',
            'description' => 'Test Category Description',
            'slug' => 'test-category',
            'icon' => '🏘️',
        ]);

        $destino = Destino::factory()->create([
            'user_id' => $user->id,
            'region_id' => $region->id,
            'status' => 'published',
        ]);
        $destino->categorias()->attach($categoria);

        // Crear promoción vigente
        $promocionVigente = PromocionDestacada::factory()->vigente()->create([
            'titulo' => 'Promoción Vigente',
            'descripcion' => 'Esta promoción está vigente',
        ]);
        $promocionVigente->destinos()->attach($destino);

        // Crear promoción expirada
        $promocionExpirada = PromocionDestacada::factory()->expirada()->create([
            'titulo' => 'Promoción Expirada',
            'descripcion' => 'Esta promoción está expirada',
        ]);

        // Hacer la petición
        $response = $this->getJson('/api/v1/public/promociones-destacadas');

        // Verificar respuesta
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'titulo',
                            'descripcion',
                            'imagen',
                            'fecha_inicio',
                            'fecha_fin',
                            'dias_restantes',
                            'destinos',
                        ]
                    ],
                    'message',
                ]);

        // Verificar que solo se devuelven las promociones vigentes
        $data = $response->json('data');
        
        // Debug: mostrar qué promociones se están devolviendo
        if (count($data) !== 1) {
            $this->fail("Se esperaban 1 promoción vigente, pero se encontraron " . count($data) . ". Promociones: " . json_encode($data));
        }
        
        $this->assertCount(1, $data);
        $this->assertEquals('Promoción Vigente', $data[0]['titulo']);
    }

    #[Test]
    public function it_can_get_specific_promocion_destacada()
    {
        // Crear datos de prueba directamente sin usar seeders
        $user = User::factory()->create();
        $user->assignRole('provider');

        $region = Region::create([
            'name' => 'Test Region',
            'description' => 'Test Region Description',
            'slug' => 'test-region',
        ]);

        $categoria = Categoria::create([
            'name' => 'Test Category',
            'description' => 'Test Category Description',
            'slug' => 'test-category',
            'icon' => '🏘️',
        ]);

        $destino = Destino::factory()->create([
            'user_id' => $user->id,
            'region_id' => $region->id,
            'status' => 'published',
        ]);
        $destino->categorias()->attach($categoria);

        $promocion = PromocionDestacada::factory()->vigente()->create([
            'titulo' => 'Promoción Específica',
            'descripcion' => 'Esta es una promoción específica',
        ]);
        $promocion->destinos()->attach($destino);

        // Hacer la petición
        $response = $this->getJson("/api/v1/public/promociones-destacadas/{$promocion->id}");

        // Verificar respuesta
        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'id',
                        'titulo',
                        'descripcion',
                        'imagen',
                        'fecha_inicio',
                        'fecha_fin',
                        'dias_restantes',
                        'esta_vigente',
                        'destinos',
                    ],
                    'message',
                ]);

        $this->assertEquals('Promoción Específica', $response->json('data.titulo'));
    }

    #[Test]
    public function it_returns_404_for_non_existent_promocion()
    {
        $response = $this->getJson('/api/v1/public/promociones-destacadas/999');

        $response->assertStatus(404);
    }
} 