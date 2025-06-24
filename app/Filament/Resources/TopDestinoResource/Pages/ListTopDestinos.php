<?php

namespace App\Filament\Resources\TopDestinoResource\Pages;

use App\Filament\Resources\TopDestinoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTopDestinos extends ListRecords
{
    protected static string $resource = TopDestinoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
