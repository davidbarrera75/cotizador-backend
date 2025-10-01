<?php

namespace App\Filament\Resources\CotizadorResource\Pages;

use App\Filament\Resources\CotizadorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCotizador extends EditRecord
{
    protected static string $resource = CotizadorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}