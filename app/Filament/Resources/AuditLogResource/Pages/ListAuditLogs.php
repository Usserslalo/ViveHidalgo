<?php

namespace App\Filament\Resources\AuditLogResource\Pages;

use App\Filament\Resources\AuditLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('clean_old_logs')
                ->label('Limpiar Logs Antiguos')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Limpiar Logs Antiguos')
                ->modalDescription('¿Estás seguro de que quieres eliminar los logs de auditoría más antiguos de 90 días? Esta acción no se puede deshacer.')
                ->modalSubmitActionLabel('Sí, limpiar logs')
                ->action(function () {
                    $deletedCount = \App\Services\AuditService::cleanOldLogs(90);
                    $this->notify('success', "Se eliminaron {$deletedCount} logs antiguos");
                }),
        ];
    }
} 