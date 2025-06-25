<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use App\Models\ReviewReport;
use Illuminate\Database\Seeder;

class ReviewReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtener algunas reseñas y usuarios existentes
        $reviews = Review::all();
        $users = User::where('id', '!=', 1)->get(); // Excluir admin
        $admins = User::role('admin')->get();

        if ($reviews->isEmpty() || $users->isEmpty()) {
            return;
        }

        // Crear reportes pendientes (asegurar que no haya duplicados)
        $this->createUniqueReports(10, 'pending', $reviews, $users);

        // Crear reportes resueltos
        $this->createUniqueReports(5, 'resolved', $reviews, $users, $admins->first()?->id ?? 1);

        // Crear reportes desestimados
        $this->createUniqueReports(5, 'dismissed', $reviews, $users, $admins->first()?->id ?? 1);

        // Crear algunos reportes con razones específicas
        $this->createUniqueReportsWithReason(3, 'pending', 'inappropriate_content', $reviews, $users);
        $this->createUniqueReportsWithReason(2, 'pending', 'spam', $reviews, $users);
        $this->createUniqueReportsWithReason(2, 'pending', 'fake_review', $reviews, $users);
    }

    /**
     * Crear reportes únicos sin duplicados
     */
    private function createUniqueReports(int $count, string $status, $reviews, $users, $resolvedBy = null): void
    {
        $created = 0;
        $attempts = 0;
        $maxAttempts = $count * 10; // Límite de intentos para evitar bucle infinito

        while ($created < $count && $attempts < $maxAttempts) {
            $review = $reviews->random();
            $user = $users->random();

            // Verificar si ya existe un reporte para esta combinación
            $existingReport = ReviewReport::where('review_id', $review->id)
                ->where('reporter_id', $user->id)
                ->exists();

            if (!$existingReport) {
                try {
                    ReviewReport::factory()
                        ->{$status}()
                        ->create([
                            'review_id' => $review->id,
                            'reporter_id' => $user->id,
                            'resolved_by' => $resolvedBy,
                        ]);
                    $created++;
                } catch (\Exception $e) {
                    // Ignorar errores y continuar
                }
            }
            $attempts++;
        }
    }

    /**
     * Crear reportes únicos con razón específica
     */
    private function createUniqueReportsWithReason(int $count, string $status, string $reason, $reviews, $users): void
    {
        $created = 0;
        $attempts = 0;
        $maxAttempts = $count * 10;

        while ($created < $count && $attempts < $maxAttempts) {
            $review = $reviews->random();
            $user = $users->random();

            // Verificar si ya existe un reporte para esta combinación
            $existingReport = ReviewReport::where('review_id', $review->id)
                ->where('reporter_id', $user->id)
                ->exists();

            if (!$existingReport) {
                try {
                    ReviewReport::factory()
                        ->{$status}()
                        ->{$reason}()
                        ->create([
                            'review_id' => $review->id,
                            'reporter_id' => $user->id,
                        ]);
                    $created++;
                } catch (\Exception $e) {
                    // Ignorar errores y continuar
                }
            }
            $attempts++;
        }
    }
} 