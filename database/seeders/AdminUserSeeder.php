<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear usuario administrador
        $admin = User::create([
            'name' => 'Administrador',
            'email' => 'admin@vivehidalgo.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        // Asignar rol de administrador
        $admin->assignRole('admin');

        $this->command->info('Usuario administrador creado:');
        $this->command->info('Email: admin@vivehidalgo.com');
        $this->command->info('Password: password');
    }
} 