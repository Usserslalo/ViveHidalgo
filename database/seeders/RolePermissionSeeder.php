<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear permisos
        $permissions = [
            // Permisos de usuarios
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            
            // Permisos de destinos
            'view-destinos',
            'create-destinos',
            'edit-destinos',
            'delete-destinos',
            'manage-destinos',
            
            // Permisos de promociones
            'view-promociones',
            'create-promociones',
            'edit-promociones',
            'delete-promociones',
            'manage-promociones',
            
            // Permisos de categorías
            'view-categorias',
            'create-categorias',
            'edit-categorias',
            'delete-categorias',
            
            // Permisos de regiones
            'view-regiones',
            'create-regiones',
            'edit-regiones',
            'delete-regiones',
            
            // Permisos de suscripciones
            'view-subscriptions',
            'create-subscriptions',
            'edit-subscriptions',
            'delete-subscriptions',
            
            // Permisos de estadísticas
            'view-stats',
            'view-provider-stats',
            'view-admin-stats',
            
            // Permisos de favoritos
            'manage-favoritos',
            
            // Permisos de administración
            'access-admin-panel',
            'manage-system',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Crear roles
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $providerRole = Role::firstOrCreate(['name' => 'provider']);
        $touristRole = Role::firstOrCreate(['name' => 'tourist']);

        // Asignar permisos al rol admin
        $adminRole->givePermissionTo(Permission::all());

        // Asignar permisos al rol provider
        $providerRole->givePermissionTo([
            'view-destinos',
            'create-destinos',
            'edit-destinos',
            'delete-destinos',
            'view-promociones',
            'create-promociones',
            'edit-promociones',
            'delete-promociones',
            'view-provider-stats',
            'manage-favoritos',
        ]);

        // Asignar permisos al rol tourist
        $touristRole->givePermissionTo([
            'view-destinos',
            'view-promociones',
            'view-categorias',
            'view-regiones',
            'manage-favoritos',
        ]);

        // Asignar rol admin al usuario existente
        $adminUser = User::where('email', 'usserslalo@gmail.com')->first();
        if ($adminUser) {
            $adminUser->assignRole('admin');
        }
    }
} 