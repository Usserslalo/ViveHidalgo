<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Destino;
use App\Models\Promocion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $provider;

    protected function setUp(): void
    {
        parent::setUp();
        
        Storage::fake('public');
        
        $this->user = User::factory()->create();
        $this->user->assignRole('tourist');
        
        $this->provider = User::factory()->create([
            'company_name' => 'Test Hotel',
            'business_type' => 'hotel',
        ]);
        $this->provider->assignRole('provider');
    }

    #[Test]
    public function user_can_get_their_profile()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/v1/user/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'roles',
                    ],
                    'is_provider',
                    'is_verified_provider',
                    'tourist_stats' => [
                        'favoritos_count',
                        'reviews_count',
                        'member_since',
                    ],
                ]
            ]);

        $this->assertFalse($response->json('data.is_provider'));
        $this->assertFalse($response->json('data.is_verified_provider'));
    }

    #[Test]
    public function provider_can_get_their_profile_with_provider_data()
    {
        $response = $this->actingAs($this->provider, 'sanctum')
            ->getJson('/api/v1/user/profile');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => [
                        'id',
                        'name',
                        'email',
                        'company_name',
                        'business_type',
                        'roles',
                    ],
                    'is_provider',
                    'is_verified_provider',
                    'provider_stats' => [
                        'destinos_count',
                        'promociones_count',
                        'total_reviews',
                        'average_rating',
                        'member_since',
                        'verified_since',
                    ],
                    'formatted_business_hours',
                    'is_open_now',
                ]
            ]);

        $this->assertTrue($response->json('data.is_provider'));
        $this->assertFalse($response->json('data.is_verified_provider'));
    }

    #[Test]
    public function user_can_update_basic_profile()
    {
        $data = [
            'name' => 'Updated Name',
            'phone' => '1234567890',
            'address' => '123 Test Street',
            'city' => 'Test City',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/user/profile/basic', $data);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'name',
                    'phone',
                    'address',
                    'city',
                ]
            ]);

        $this->user->refresh();
        $this->assertEquals('Updated Name', $this->user->name);
        $this->assertEquals('1234567890', $this->user->phone);
    }

    #[Test]
    public function provider_can_update_provider_profile()
    {
        $data = [
            'company_name' => 'Updated Hotel',
            'company_description' => 'A great hotel for tourists',
            'website' => 'https://example.com',
            'business_type' => 'hotel',
            'business_hours' => [
                'monday' => ['open' => '09:00', 'close' => '18:00'],
                'tuesday' => ['open' => '09:00', 'close' => '18:00'],
                'wednesday' => ['closed' => true],
            ],
        ];

        $response = $this->actingAs($this->provider, 'sanctum')
            ->putJson('/api/v1/user/profile/provider', $data);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'company_name',
                    'company_description',
                    'website',
                    'business_type',
                    'business_hours',
                ]
            ]);

        $this->provider->refresh();
        $this->assertEquals('Updated Hotel', $this->provider->company_name);
        $this->assertEquals('hotel', $this->provider->business_type);
    }

    #[Test]
    public function non_provider_cannot_update_provider_profile()
    {
        $data = [
            'company_name' => 'Test Company',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->putJson('/api/v1/user/profile/provider', $data);

        $response->assertStatus(403);
    }

    #[Test]
    public function provider_can_upload_logo()
    {
        $file = UploadedFile::fake()->image('logo.png', 100, 100);

        $response = $this->actingAs($this->provider, 'sanctum')
            ->postJson('/api/v1/user/profile/logo', [
                'logo' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'logo_url',
                    'logo_path',
                ]
            ]);

        $this->provider->refresh();
        $this->assertNotNull($this->provider->logo_path);
        $this->assertNotNull($this->provider->logo_url);
    }

    #[Test]
    public function provider_can_upload_business_license()
    {
        $file = UploadedFile::fake()->create('license.pdf', 100);

        $response = $this->actingAs($this->provider, 'sanctum')
            ->postJson('/api/v1/user/profile/license', [
                'license' => $file,
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'license_url',
                    'license_path',
                ]
            ]);

        $this->provider->refresh();
        $this->assertNotNull($this->provider->business_license_path);
        $this->assertNotNull($this->provider->business_license_url);
    }

    #[Test]
    public function non_provider_cannot_upload_logo()
    {
        $file = UploadedFile::fake()->image('logo.png', 100, 100);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/user/profile/logo', [
                'logo' => $file,
            ]);

        $response->assertStatus(403);
    }

    #[Test]
    public function provider_can_delete_logo()
    {
        // Primero subir un logo
        $file = UploadedFile::fake()->image('logo.png', 100, 100);
        $this->actingAs($this->provider, 'sanctum')
            ->postJson('/api/v1/user/profile/logo', ['logo' => $file]);

        $this->provider->refresh();
        $this->assertNotNull($this->provider->logo_path);

        // Luego eliminarlo
        $response = $this->actingAs($this->provider, 'sanctum')
            ->deleteJson('/api/v1/user/profile/logo');

        $response->assertStatus(200);

        $this->provider->refresh();
        $this->assertNull($this->provider->logo_path);
    }

    #[Test]
    public function provider_can_delete_business_license()
    {
        // Primero subir una licencia
        $file = UploadedFile::fake()->create('license.pdf', 100);
        $this->actingAs($this->provider, 'sanctum')
            ->postJson('/api/v1/user/profile/license', ['license' => $file]);

        $this->provider->refresh();
        $this->assertNotNull($this->provider->business_license_path);

        // Luego eliminarla
        $response = $this->actingAs($this->provider, 'sanctum')
            ->deleteJson('/api/v1/user/profile/license');

        $response->assertStatus(200);

        $this->provider->refresh();
        $this->assertNull($this->provider->business_license_path);
    }

    #[Test]
    public function user_can_change_password()
    {
        $data = [
            'current_password' => 'password', // password por defecto en factory
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/user/profile/change-password', $data);

        $response->assertStatus(200);

        $this->user->refresh();
        $this->assertTrue(\Hash::check('newpassword123', $this->user->password));
    }

    #[Test]
    public function user_cannot_change_password_with_wrong_current_password()
    {
        $data = [
            'current_password' => 'wrongpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/v1/user/profile/change-password', $data);

        $response->assertStatus(422);
    }

    #[Test]
    public function user_can_delete_account()
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson('/api/v1/user/profile/account');

        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $this->user->id]);
    }

    #[Test]
    public function provider_can_delete_account_with_files()
    {
        // Subir archivos primero
        $logo = UploadedFile::fake()->image('logo.png', 100, 100);
        $license = UploadedFile::fake()->create('license.pdf', 100);
        
        $this->actingAs($this->provider, 'sanctum')
            ->postJson('/api/v1/user/profile/logo', ['logo' => $logo]);
        $this->actingAs($this->provider, 'sanctum')
            ->postJson('/api/v1/user/profile/license', ['license' => $license]);

        $this->provider->refresh();
        $this->assertNotNull($this->provider->logo_path);
        $this->assertNotNull($this->provider->business_license_path);

        // Eliminar cuenta
        $response = $this->actingAs($this->provider, 'sanctum')
            ->deleteJson('/api/v1/user/profile/account');

        $response->assertStatus(200);

        $this->assertDatabaseMissing('users', ['id' => $this->provider->id]);
    }

    #[Test]
    public function provider_stats_are_calculated_correctly()
    {
        // Crear destinos y promociones para el proveedor
        $destino = Destino::factory()->create(['provider_id' => $this->provider->id]);
        Promocion::factory()->create(['provider_id' => $this->provider->id]);

        $response = $this->actingAs($this->provider, 'sanctum')
            ->getJson('/api/v1/user/profile');

        $response->assertStatus(200);

        $stats = $response->json('data.provider_stats');
        $this->assertEquals(1, $stats['destinos_count']);
        $this->assertEquals(1, $stats['promociones_count']);
    }

    #[Test]
    public function business_hours_are_formatted_correctly()
    {
        $this->provider->update([
            'business_hours' => [
                'monday' => ['open' => '09:00', 'close' => '18:00'],
                'tuesday' => ['closed' => true],
            ],
        ]);

        $response = $this->actingAs($this->provider, 'sanctum')
            ->getJson('/api/v1/user/profile');

        $response->assertStatus(200);

        $formattedHours = $response->json('data.formatted_business_hours');
        $this->assertArrayHasKey('Lunes', $formattedHours);
        $this->assertArrayHasKey('Martes', $formattedHours);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_profile()
    {
        $response = $this->getJson('/api/v1/user/profile');

        $response->assertStatus(401);
    }
} 