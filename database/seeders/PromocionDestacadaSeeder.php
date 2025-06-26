<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PromocionDestacada;
use App\Models\Destino;

class PromocionDestacadaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear promociones de ejemplo
        $promociones = [
            [
                'titulo' => 'Verano en la Huasteca',
                'descripcion' => 'Descubre los paisajes más hermosos de la Huasteca Hidalguense con descuentos especiales en hospedaje y actividades.',
                'imagen' => 'https://via.placeholder.com/800x400/10B981/FFFFFF?text=Verano+en+la+Huasteca',
                'fecha_inicio' => now(),
                'fecha_fin' => now()->addDays(30),
                'is_active' => true,
            ],
            [
                'titulo' => 'Pueblos Mágicos de Hidalgo',
                'descripcion' => 'Explora la magia de nuestros pueblos con paquetes turísticos completos que incluyen transporte, hospedaje y guías locales.',
                'imagen' => 'https://via.placeholder.com/800x400/8B5CF6/FFFFFF?text=Pueblos+Mágicos',
                'fecha_inicio' => now()->addDays(5),
                'fecha_fin' => now()->addDays(45),
                'is_active' => true,
            ],
            [
                'titulo' => 'Aventura en la Sierra',
                'descripcion' => 'Vive experiencias llenas de adrenalina en la Sierra de Hidalgo con actividades como rappel, senderismo y camping.',
                'imagen' => 'https://via.placeholder.com/800x400/F59E0B/FFFFFF?text=Aventura+en+la+Sierra',
                'fecha_inicio' => now()->addDays(10),
                'fecha_fin' => now()->addDays(60),
                'is_active' => true,
            ],
        ];

        foreach ($promociones as $promocionData) {
            $promocion = PromocionDestacada::create($promocionData);

            // Asignar algunos destinos aleatorios a cada promoción
            $destinos = Destino::published()->inRandomOrder()->limit(rand(2, 5))->get();
            $promocion->destinos()->attach($destinos);
        }

        $this->command->info('Promociones destacadas creadas exitosamente.');
    }
}