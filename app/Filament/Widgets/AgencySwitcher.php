<?php

namespace App\Filament\Widgets;

use App\Models\Agency;
use Filament\Widgets\Widget;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class AgencySwitcher extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.agency-switcher';
    protected int | string | array $columnSpan = 'full';
    protected static ?int $sort = -1;

    public ?int $selectedAgency = null;

    public function mount(): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $this->selectedAgency = session('selected_agency_id');
        }
    }

    public function updatedSelectedAgency($value): void
    {
        $user = auth()->user();

        if ($user->isSuperAdmin()) {
            $user->setCurrentAgency($value);

            // Redireccionar para refrescar la página
            redirect()->to(request()->header('Referer') ?? '/admin');
        }
    }

    public function getAgencies(): array
    {
        return Agency::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public static function canView(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
