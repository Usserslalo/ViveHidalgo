<?php

namespace Database\Seeders;

use App\Models\Region;
use Illuminate\Database\Seeder;

class RegionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $regiones = [
            [
                'name' => 'Sierra Gorda',
                'description' => 'Región montañosa con paisajes espectaculares',
                'slug' => 'sierra-gorda',
            ],
            [
                'name' => 'Huasteca',
                'description' => 'Región tropical con cascadas y ríos',
                'slug' => 'huasteca',
            ],
            [
                'name' => 'Altiplano',
                'description' => 'Región de llanuras y clima templado',
                'slug' => 'altiplano',
            ],
            [
                'name' => 'Valle del Mezquital',
                'description' => 'Región árida con cultura otomí',
                'slug' => 'valle-del-mezquital',
            ],
        ];

        foreach ($regiones as $region) {
            Region::firstOrCreate(
                ['slug' => $region['slug']],
                $region
            );
        }
    }
} 