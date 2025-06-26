<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\HomeConfig;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Ejecutar el seeder de roles y permisos primero
        $this->call([
            RolePermissionSeeder::class,
            AdminUserSeeder::class,
            RegionSeeder::class,
            CategoriaSeeder::class,
            CaracteristicaSeeder::class,
            TagSeeder::class,
            DestinoSeeder::class,
            HomeConfigSeeder::class,
            UserSeeder::class,
            ReviewReportSeeder::class,
            PromocionDestacadaSeeder::class,
        ]);

        // Crear usuario administrador si no existe
        $adminUser = User::firstOrCreate(
            ['email' => 'usserslalo@gmail.com'],
            [
                'name' => 'Admin User',
                'password' => bcrypt('password'), // ¡Cambiar en producción!
                'is_active' => true,
            ]
        );
        $adminUser->assignRole('admin');

        // Crear usuario de prueba si no existe
        User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
                'is_active' => true,
            ]
        )->assignRole('tourist');
    }
}
