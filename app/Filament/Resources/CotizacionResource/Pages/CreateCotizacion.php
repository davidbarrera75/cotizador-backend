<?php

namespace App\Filament\Resources\CotizacionResource\Pages;

use App\Filament\Resources\CotizacionResource;
//use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCotizacion extends CreateRecord
{
    protected static string $resource = CotizacionResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        
        // Si no es super admin, asignar su agencia
        if (!$user->isSuperAdmin()) {
            $agency = $user->getCurrentAgency();
            if ($agency) {
                $data['agency_id'] = $agency->id;
            }
        } else {
            // Si es super admin, obtener agency_id del cotizador seleccionado
            if (isset($data['cotizador_id'])) {
                $cotizador = \App\Models\Cotizador::find($data['cotizador_id']);
                if ($cotizador) {
                    $data['agency_id'] = $cotizador->agency_id;
                }
            }
        }
        
        return $data;
    }
}
