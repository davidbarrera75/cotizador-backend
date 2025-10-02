<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AgencySwitcher;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function getWidgets(): array
    {
        $widgets = [];

        // Agregar el selector de agencia para super admins
        if (auth()->user()?->isSuperAdmin()) {
            $widgets[] = AgencySwitcher::class;
        }

        // Agregar los widgets por defecto
        $widgets = array_merge($widgets, parent::getWidgets());

        return $widgets;
    }
}
