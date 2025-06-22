<?php

namespace Database\Seeders;

use App\Models\Caracteristica;
use Illuminate\Database\Seeder;

class CaracteristicaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $caracteristicas = [
            // Amenidades básicas
            ['nombre' => 'WiFi', 'tipo' => 'amenidad', 'icono' => 'fas fa-wifi', 'descripcion' => 'Conexión inalámbrica a internet disponible'],
            ['nombre' => 'Estacionamiento', 'tipo' => 'amenidad', 'icono' => 'fas fa-parking', 'descripcion' => 'Área de estacionamiento disponible'],
            ['nombre' => 'Aire acondicionado', 'tipo' => 'amenidad', 'icono' => 'fas fa-snowflake', 'descripcion' => 'Sistema de aire acondicionado'],
            ['nombre' => 'Restaurante', 'tipo' => 'amenidad', 'icono' => 'fas fa-utensils', 'descripcion' => 'Restaurante en el lugar'],
            ['nombre' => 'Bar', 'tipo' => 'amenidad', 'icono' => 'fas fa-glass-martini', 'descripcion' => 'Bar disponible'],
            ['nombre' => 'Piscina', 'tipo' => 'amenidad', 'icono' => 'fas fa-swimming-pool', 'descripcion' => 'Piscina disponible'],
            ['nombre' => 'Gimnasio', 'tipo' => 'amenidad', 'icono' => 'fas fa-dumbbell', 'descripcion' => 'Gimnasio equipado'],
            ['nombre' => 'Spa', 'tipo' => 'amenidad', 'icono' => 'fas fa-spa', 'descripcion' => 'Servicios de spa'],
            ['nombre' => 'Terraza', 'tipo' => 'amenidad', 'icono' => 'fas fa-umbrella-beach', 'descripcion' => 'Terraza con vista'],
            ['nombre' => 'Jardín', 'tipo' => 'amenidad', 'icono' => 'fas fa-seedling', 'descripcion' => 'Jardín privado'],
            
            // Características especiales
            ['nombre' => 'Pet-friendly', 'tipo' => 'especial', 'icono' => 'fas fa-paw', 'descripcion' => 'Acepta mascotas'],
            ['nombre' => 'Accesible', 'tipo' => 'especial', 'icono' => 'fas fa-wheelchair', 'descripcion' => 'Accesible para personas con discapacidad'],
            ['nombre' => 'Familiar', 'tipo' => 'especial', 'icono' => 'fas fa-baby', 'descripcion' => 'Ideal para familias'],
            ['nombre' => 'Romántico', 'tipo' => 'especial', 'icono' => 'fas fa-heart', 'descripcion' => 'Ambiente romántico'],
            ['nombre' => 'Ecológico', 'tipo' => 'especial', 'icono' => 'fas fa-leaf', 'descripcion' => 'Prácticas ecológicas'],
            ['nombre' => 'Tranquilo', 'tipo' => 'especial', 'icono' => 'fas fa-peace', 'descripcion' => 'Ambiente tranquilo y relajante'],
            
            // Tipos de alojamiento
            ['nombre' => 'Cabañas', 'tipo' => 'alojamiento', 'icono' => 'fas fa-home', 'descripcion' => 'Cabañas rústicas'],
            ['nombre' => 'Hotel', 'tipo' => 'alojamiento', 'icono' => 'fas fa-hotel', 'descripcion' => 'Hotel tradicional'],
            ['nombre' => 'Hostal', 'tipo' => 'alojamiento', 'icono' => 'fas fa-bed', 'descripcion' => 'Hostal económico'],
            ['nombre' => 'Camping', 'tipo' => 'alojamiento', 'icono' => 'fas fa-campground', 'descripcion' => 'Área de camping'],
            ['nombre' => 'Casa rural', 'tipo' => 'alojamiento', 'icono' => 'fas fa-house-user', 'descripcion' => 'Casa rural tradicional'],
            
            // Actividades
            ['nombre' => 'Senderismo', 'tipo' => 'actividad', 'icono' => 'fas fa-hiking', 'descripcion' => 'Rutas de senderismo disponibles'],
            ['nombre' => 'Ciclismo', 'tipo' => 'actividad', 'icono' => 'fas fa-bicycle', 'descripcion' => 'Rutas para ciclismo'],
            ['nombre' => 'Pesca', 'tipo' => 'actividad', 'icono' => 'fas fa-fish', 'descripcion' => 'Actividades de pesca'],
            ['nombre' => 'Escalada', 'tipo' => 'actividad', 'icono' => 'fas fa-mountain', 'descripcion' => 'Zonas de escalada'],
            ['nombre' => 'Rappel', 'tipo' => 'actividad', 'icono' => 'fas fa-arrow-down', 'descripcion' => 'Actividades de rappel'],
            ['nombre' => 'Paseos a caballo', 'tipo' => 'actividad', 'icono' => 'fas fa-horse', 'descripcion' => 'Paseos ecuestres'],
            ['nombre' => 'Observación de aves', 'tipo' => 'actividad', 'icono' => 'fas fa-dove', 'descripcion' => 'Ideal para observación de aves'],
            
            // Características culturales
            ['nombre' => 'Pueblo Mágico', 'tipo' => 'cultural', 'icono' => 'fas fa-star', 'descripcion' => 'Designado como Pueblo Mágico'],
            ['nombre' => 'Sitio histórico', 'tipo' => 'cultural', 'icono' => 'fas fa-landmark', 'descripcion' => 'Sitio de importancia histórica'],
            ['nombre' => 'Museo', 'tipo' => 'cultural', 'icono' => 'fas fa-museum', 'descripcion' => 'Museo o galería'],
            ['nombre' => 'Artesanías', 'tipo' => 'cultural', 'icono' => 'fas fa-palette', 'descripcion' => 'Artesanías locales'],
            ['nombre' => 'Gastronomía local', 'tipo' => 'cultural', 'icono' => 'fas fa-utensils', 'descripcion' => 'Gastronomía típica de la región'],
            ['nombre' => 'Festivales', 'tipo' => 'cultural', 'icono' => 'fas fa-calendar-alt', 'descripcion' => 'Festivales y eventos culturales'],
            
            // Características naturales
            ['nombre' => 'Cascada', 'tipo' => 'natural', 'icono' => 'fas fa-water', 'descripcion' => 'Cascada natural'],
            ['nombre' => 'Bosque', 'tipo' => 'natural', 'icono' => 'fas fa-tree', 'descripcion' => 'Bosque o área boscosa'],
            ['nombre' => 'Río', 'tipo' => 'natural', 'icono' => 'fas fa-water', 'descripcion' => 'Río o arroyo'],
            ['nombre' => 'Mirador', 'tipo' => 'natural', 'icono' => 'fas fa-binoculars', 'descripcion' => 'Mirador con vistas panorámicas'],
            ['nombre' => 'Grutas', 'tipo' => 'natural', 'icono' => 'fas fa-cave', 'descripcion' => 'Grutas o cuevas'],
            ['nombre' => 'Manantial', 'tipo' => 'natural', 'icono' => 'fas fa-tint', 'descripcion' => 'Manantial de agua'],
            ['nombre' => 'Observación de estrellas', 'tipo' => 'natural', 'icono' => 'fas fa-star', 'descripcion' => 'Ideal para observación astronómica'],
        ];

        foreach ($caracteristicas as $caracteristica) {
            Caracteristica::create($caracteristica);
        }

        $this->command->info('Características sembradas exitosamente.');
    }
} 