<?php

namespace Database\Seeders;

use App\Models\Destino;
use App\Models\Region;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DestinoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear un usuario proveedor si no existe
        $provider = User::firstOrCreate(
            ['email' => 'provider@example.com'],
            array_merge(
                User::factory()->make()->toArray(),
                ['password' => bcrypt('password')]
            )
        );
        $provider->assignRole('provider');

        // Crear una región si no existe
        $region = Region::firstOrCreate(
            ['slug' => 'comarca-minera'],
            ['name' => 'Comarca Minera']
        );

        // Crear 5 destinos asociados al proveedor y la región
        Destino::factory()->count(5)->create([
            'user_id' => $provider->id,
            'region_id' => $region->id,
        ]);

        $this->command->info('5 destinos de prueba creados exitosamente.');
    }
} 