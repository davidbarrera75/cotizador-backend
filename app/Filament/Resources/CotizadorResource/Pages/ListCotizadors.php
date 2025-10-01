<?php

namespace App\Filament\Resources\CotizadorResource\Pages;

use App\Filament\Resources\CotizadorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCotizadors extends ListRecords
{
    protected static string $resource = CotizadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
