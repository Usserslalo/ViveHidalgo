<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear 10 usuarios con el rol 'tourist'
        User::factory()->count(10)->create()->each(function ($user) {
            $user->assignRole('tourist');
        });

        $this->command->info('10 usuarios de tipo turista creados exitosamente.');
    }
} 