<?php

namespace App\Filament\Resources\TopDestinoResource\Pages;

use App\Filament\Resources\TopDestinoResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTopDestino extends CreateRecord
{
    protected static string $resource = TopDestinoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['is_top'] = true;
        $data['user_id'] = auth()->id();
        
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
