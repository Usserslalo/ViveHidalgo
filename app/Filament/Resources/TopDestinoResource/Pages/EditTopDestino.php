<?php

namespace App\Filament\Resources\TopDestinoResource\Pages;

use App\Filament\Resources\TopDestinoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTopDestino extends EditRecord
{
    protected static string $resource = TopDestinoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['is_top'] = true;
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
