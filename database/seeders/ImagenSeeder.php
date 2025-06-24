<?php

namespace Database\Seeders;

use App\Models\Destino;
use App\Models\Imagen;
use App\Models\Promocion;
use App\Models\Region;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ImagenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear imágenes para destinos
        $destinos = Destino::all();
        foreach ($destinos as $destino) {
            // Imagen principal
            Imagen::create([
                'imageable_type' => Destino::class,
                'imageable_id' => $destino->id,
                'path' => 'images/destinos/placeholder.jpg',
                'alt' => 'Imagen principal de ' . $destino->name,
                'orden' => 0,
                'is_main' => true,
                'disk' => 'public',
                'mime_type' => 'image/jpeg',
                'size' => rand(200000, 800000),
            ]);

            // Imágenes adicionales (1-3 por destino)
            $numImagenes = rand(1, 3);
            for ($i = 1; $i <= $numImagenes; $i++) {
                Imagen::create([
                    'imageable_type' => Destino::class,
                    'imageable_id' => $destino->id,
                    'path' => 'images/destinos/placeholder-' . $i . '.jpg',
                    'alt' => 'Imagen ' . $i . ' de ' . $destino->name,
                    'orden' => $i,
                    'is_main' => false,
                    'disk' => 'public',
                    'mime_type' => 'image/jpeg',
                    'size' => rand(150000, 600000),
                ]);
            }
        }

        // Crear imágenes para promociones
        $promociones = Promocion::all();
        foreach ($promociones as $promocion) {
            Imagen::create([
                'imageable_type' => Promocion::class,
                'imageable_id' => $promocion->id,
                'path' => 'images/promociones/placeholder.jpg',
                'alt' => 'Imagen de promoción: ' . $promocion->title,
                'orden' => 0,
                'is_main' => true,
                'disk' => 'public',
                'mime_type' => 'image/jpeg',
                'size' => rand(100000, 500000),
            ]);
        }

        // Crear imágenes para regiones
        $regiones = Region::all();
        foreach ($regiones as $region) {
            Imagen::create([
                'imageable_type' => Region::class,
                'imageable_id' => $region->id,
                'path' => 'images/regiones/placeholder.jpg',
                'alt' => 'Imagen de la región: ' . $region->name,
                'orden' => 0,
                'is_main' => true,
                'disk' => 'public',
                'mime_type' => 'image/jpeg',
                'size' => rand(300000, 1000000),
            ]);
        }

        $this->command->info('Imágenes de prueba creadas exitosamente.');
    }
} 