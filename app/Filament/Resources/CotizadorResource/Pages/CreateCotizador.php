<?php

namespace App\Filament\Resources\CotizadorResource\Pages;

use App\Filament\Resources\CotizadorResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;

class CreateCotizador extends CreateRecord
{
    protected static string $resource = CotizadorResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Si es super admin en modo vista de agencia específica
        if ($user->isSuperAdmin() && $user->getCurrentAgency()) {
            $agency = $user->getCurrentAgency();

            // Verificar si puede agregar más cotizadores
            if (!$agency->canAddCotizador()) {
                Notification::make()
                    ->danger()
                    ->title('Límite alcanzado')
                    ->body("Esta agencia ha alcanzado el límite de {$agency->max_cotizadores} cotizadores para su plan actual.")
                    ->send();

                $this->halt();
            }

            // Asignar la agencia del contexto actual
            $data['agency_id'] = $agency->id;
        }
        // Si es usuario normal de agencia
        elseif (!$user->isSuperAdmin()) {
            $agency = $user->getCurrentAgency();

            if (!$agency) {
                Notification::make()
                    ->danger()
                    ->title('Error')
                    ->body('No tienes una agencia asignada.')
                    ->send();

                $this->halt();
            }

            // Verificar si puede agregar más cotizadores
            if (!$agency->canAddCotizador()) {
                Notification::make()
                    ->danger()
                    ->title('Límite alcanzado')
                    ->body("Has alcanzado el límite de {$agency->max_cotizadores} cotizadores para tu plan actual.")
                    ->send();

                $this->halt();
            }

            // Asignar automáticamente la agencia
            $data['agency_id'] = $agency->id;
        }
        // Si es super admin sin agencia seleccionada, debe seleccionar manualmente

        return $data;
    }
}