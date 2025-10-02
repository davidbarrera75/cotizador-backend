<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CotizadorResource\Pages;
use App\Models\Cotizador;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CotizadorResource extends Resource
{
    protected static ?string $model = Cotizador::class;
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Cotizadores';
    protected static ?string $modelLabel = 'Cotizador';
    protected static ?string $pluralModelLabel = 'Cotizadores';

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        // Si es super admin
        if ($user->isSuperAdmin()) {
            // Si tiene una agencia seleccionada en el contexto, filtrar por esa
            $currentAgency = $user->getCurrentAgency();
            if ($currentAgency) {
                return $query->where('agency_id', $currentAgency->id);
            }
            // Si no, ve todo
            return $query;
        }

        // Si es usuario normal, filtrar por TODAS sus agencias
        $agencyIds = $user->agencies()->pluck('agencies.id')->toArray();

        if (!empty($agencyIds)) {
            return $query->whereIn('agency_id', $agencyIds);
        }

        // Si no tiene agencias, no ve nada
        return $query->whereNull('id');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // SECCIÓN: AGENCIA (solo visible para super admin sin agencia seleccionada)
                Forms\Components\Section::make('Asignación de Agencia')
                    ->description('Selecciona la agencia propietaria de este cotizador')
                    ->schema([
                        Forms\Components\Select::make('agency_id')
                            ->label('Agencia')
                            ->relationship('agency', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Selecciona la agencia a la que pertenece este cotizador'),
                    ])
                    ->visible(fn () => auth()->user()->isSuperAdmin() && !auth()->user()->getCurrentAgency())
                    ->collapsible(),

                // SECCIÓN: INFORMACIÓN GENERAL
                Forms\Components\Section::make('Información General')
                    ->description('Datos básicos del hotel o tour')
                    ->schema([
                        Forms\Components\TextInput::make('hotel_name')
                            ->label('Nombre del Hotel/Tour')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Hotel Colina del Sol'),

                        Forms\Components\TextInput::make('slug')
                            ->label('Slug (URL amigable)')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->alphaDash()
                            ->placeholder('hotel-colina-del-sol')
                            ->helperText('Solo letras, números y guiones. Ejemplo: hotel-colina-del-sol'),

                        Forms\Components\Textarea::make('hotel_slogan')
                            ->label('Slogan')
                            ->required()
                            ->maxLength(500)
                            ->rows(2)
                            ->placeholder('Planes de viaje para todos los gustos...'),

                        Forms\Components\TextInput::make('price_validity')
                            ->label('Vigencia de Precios')
                            ->maxLength(500)
                            ->placeholder('PRECIOS POR PERSONA (vigencia hasta...)'),

                        Forms\Components\TextInput::make('whatsapp_number')
                            ->label('Número de WhatsApp')
                            ->required()
                            ->tel()
                            ->maxLength(20)
                            ->placeholder('573506686684')
                            ->helperText('Solo números, sin + ni espacios. Ej: 573506686684'),

                        Forms\Components\TextInput::make('operator_info')
                            ->label('Información del Operador Turístico')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Operado por Zuma Travel - RNT 144753'),

                        Forms\Components\Toggle::make('active')
                            ->label('Cotizador Activo')
                            ->default(true)
                            ->helperText('Desactiva para ocultar temporalmente'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // SECCIÓN: PLANES Y TARIFAS
                Forms\Components\Section::make('Planes y Tarifas')
                    ->description('Configura los diferentes planes que ofreces')
                    ->schema([
                        Forms\Components\Repeater::make('plans')
                            ->label('')
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        Forms\Components\TextInput::make('key')
                                            ->label('Identificador')
                                            ->required()
                                            ->alphaDash()
                                            ->placeholder('basico')
                                            ->helperText('Sin espacios: basico, terrestre, grupos'),

                                        Forms\Components\TextInput::make('name')
                                            ->label('Nombre del Plan')
                                            ->required()
                                            ->placeholder('Plan Básico'),

                                        Forms\Components\TextInput::make('minPeople')
                                            ->label('Mínimo de Personas')
                                            ->numeric()
                                            ->required()
                                            ->default(1)
                                            ->minValue(1)
                                            ->maxValue(100),
                                    ]),

                                Forms\Components\Fieldset::make('Precios por Tipo de Habitación')
                                    ->schema([
                                        Forms\Components\TextInput::make('prices.sencilla')
                                            ->label('Habitación Sencilla')
                                            ->numeric()
                                            ->prefix('$')
                                            ->placeholder('Vacío = No disponible'),

                                        Forms\Components\TextInput::make('prices.doble')
                                            ->label('Habitación Doble')
                                            ->numeric()
                                            ->prefix('$')
                                            ->placeholder('Vacío = No disponible'),

                                        Forms\Components\TextInput::make('prices.triple')
                                            ->label('Habitación Triple')
                                            ->numeric()
                                            ->prefix('$')
                                            ->placeholder('Vacío = No disponible'),

                                        Forms\Components\TextInput::make('prices.cuadruple')
                                            ->label('Habitación Cuádruple')
                                            ->numeric()
                                            ->prefix('$')
                                            ->placeholder('Vacío = No disponible'),

                                        Forms\Components\TextInput::make('prices.nino')
                                            ->label('Tarifa Niño')
                                            ->numeric()
                                            ->prefix('$')
                                            ->placeholder('Vacío = No disponible'),
                                    ])
                                    ->columns(3),

                                Forms\Components\Textarea::make('nino_desc')
                                    ->label('Descripción de Tarifa para Niños')
                                    ->rows(2)
                                    ->placeholder('Ejemplo: Niños de 5 años en adelante')
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => $state['name'] ?? 'Nuevo Plan')
                            ->defaultItems(1)
                            ->addActionLabel('Agregar Otro Plan')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // SECCIÓN: SERVICIOS ADICIONALES
                Forms\Components\Section::make('Servicios Adicionales')
                    ->description('Tarifas para noches y comidas extra')
                    ->schema([
                        Forms\Components\Fieldset::make('Precio por Noche Adicional')
                            ->schema([
                                Forms\Components\TextInput::make('adicionales.noche.sencilla')
                                    ->label('Hab. Sencilla')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('Vacío = No disponible'),

                                Forms\Components\TextInput::make('adicionales.noche.doble')
                                    ->label('Hab. Doble')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('Vacío = No disponible'),

                                Forms\Components\TextInput::make('adicionales.noche.triple')
                                    ->label('Hab. Triple')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('Vacío = No disponible'),

                                Forms\Components\TextInput::make('adicionales.noche.cuadruple')
                                    ->label('Hab. Cuádruple')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('Vacío = No disponible'),

                                Forms\Components\TextInput::make('adicionales.noche.nino')
                                    ->label('Niño')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('Vacío = No disponible'),
                            ])
                            ->columns(3),

                        Forms\Components\TextInput::make('adicionales.comida')
                            ->label('Precio por Comida Adicional (Almuerzo o Cena)')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('28000')
                            ->helperText('Precio por persona. Vacío = No disponible'),
                    ])
                    ->collapsible(),

                // SECCIÓN: INFORMACIÓN PARA CLIENTES
                Forms\Components\Section::make('Información para el Cliente')
                    ->description('Secciones desplegables con detalles del plan')
                    ->schema([
                        Forms\Components\Repeater::make('info_sections')
                            ->label('')
                            ->schema([
                                Forms\Components\TextInput::make('title')
                                    ->label('Título de la Sección')
                                    ->placeholder('¿Qué incluye el plan?'),

                                Forms\Components\RichEditor::make('content')
                                    ->label('Contenido')
                                    ->toolbarButtons([
                                        'bold',
                                        'italic',
                                        'bulletList',
                                        'orderedList',
                                    ])
                                    ->placeholder('Escribe el contenido aquí...')
                                    ->columnSpanFull(),
                            ])
                            ->collapsible()
                            ->cloneable()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? 'Nueva Sección')
                            ->defaultItems(0)
                            ->addActionLabel('Agregar Otra Sección')
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hotel_name')
                    ->label('Hotel/Tour')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('agency.name')
                    ->label('Agencia')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->visible(fn () => auth()->user()->isSuperAdmin()),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Slug copiado al portapapeles')
                    ->badge()
                    ->color('info'),

                Tables\Columns\IconColumn::make('active')
                    ->label('Estado')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('last_export_at')
                    ->label('Última Exportación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->placeholder('No exportado')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('export_filename')
                    ->label('Archivo HTML')
                    ->copyable()
                    ->copyMessage('Nombre copiado')
                    ->placeholder('Sin exportar')
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Solo Activos')
                    ->falseLabel('Solo Inactivos'),
            ])
            ->actions([
                Tables\Actions\Action::make('transferToAgency')
                    ->label('Transferir')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('agency_id')
                            ->label('Agencia Destino')
                            ->options(\App\Models\Agency::pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->helperText('Selecciona la agencia a la que quieres asignar este cotizador'),
                    ])
                    ->action(function (Cotizador $record, array $data): void {
                        $agency = \App\Models\Agency::find($data['agency_id']);

                        // Verificar límite de cotizadores de la agencia
                        if (!$agency->canAddCotizador() && $record->agency_id !== $agency->id) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('Límite alcanzado')
                                ->body("La agencia {$agency->name} ha alcanzado su límite de {$agency->max_cotizadores} cotizadores.")
                                ->send();
                            return;
                        }

                        $oldAgencyName = $record->agency ? $record->agency->name : 'Sin asignar';

                        $record->update([
                            'agency_id' => $data['agency_id'],
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Cotizador transferido')
                            ->body("El cotizador fue transferido de '{$oldAgencyName}' a '{$agency->name}'.")
                            ->send();
                    })
                    ->modalHeading('Transferir Cotizador a Agencia')
                    ->modalSubmitActionLabel('Transferir')
                    ->visible(fn () => auth()->user()->isSuperAdmin()),

                Tables\Actions\ReplicateAction::make()
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('info')
                    ->excludeAttributes(['slug'])
                    ->beforeReplicaSaved(function (Cotizador $replica): void {
                        $baseSlug = $replica->slug . '-copia';
                        $slug = $baseSlug;
                        $counter = 1;

                        while (Cotizador::where('slug', $slug)->exists()) {
                            $slug = $baseSlug . '-' . $counter;
                            $counter++;
                        }

                        $replica->slug = $slug;
                        $replica->hotel_name = $replica->hotel_name . ' (Copia)';
                        $replica->active = false;
                    })
                    ->successNotificationTitle('Cotizador duplicado correctamente'),

                Tables\Actions\Action::make('preview')
                    ->label('Vista Previa')
                    ->icon('heroicon-o-eye')
                    ->color('warning')
                    ->url(fn (Cotizador $record): string => route('cotizador.preview', $record))
                    ->openUrlInNewTab(),

                Tables\Actions\EditAction::make(),

                Tables\Actions\Action::make('exportHtml')
                    ->label('Exportar HTML')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function (Cotizador $record) {
                        $exporter = new \App\Services\CotizadorExportService();
                        $filePath = $exporter->exportToHtml($record);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('HTML Exportado')
                            ->body('El archivo se ha generado correctamente.')
                            ->send();

                        return response()->download($filePath);
                    }),

                Tables\Actions\Action::make('exportJson')
                    ->label('Exportar JSON')
                    ->icon('heroicon-o-code-bracket')
                    ->color('info')
                    ->action(function (Cotizador $record) {
                        $exporter = new \App\Services\CotizadorExportService();
                        $filePath = $exporter->exportToJson($record);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('JSON Exportado')
                            ->body('El archivo JSON se ha generado correctamente.')
                            ->send();

                        return response()->download($filePath);
                    }),

                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('bulkTransfer')
                        ->label('Transferir a Agencia')
                        ->icon('heroicon-o-arrow-right-circle')
                        ->color('warning')
                        ->form([
                            Forms\Components\Select::make('agency_id')
                                ->label('Agencia Destino')
                                ->options(\App\Models\Agency::pluck('name', 'id'))
                                ->required()
                                ->searchable()
                                ->helperText('Todos los cotizadores seleccionados serán transferidos a esta agencia'),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data): void {
                            $agency = \App\Models\Agency::find($data['agency_id']);
                            $transferred = 0;
                            $skipped = 0;

                            foreach ($records as $record) {
                                // Verificar si la agencia tiene espacio (solo si es una nueva asignación)
                                if ($record->agency_id !== $agency->id) {
                                    $currentCount = $agency->cotizadores()->count();
                                    if ($currentCount >= $agency->max_cotizadores) {
                                        $skipped++;
                                        continue;
                                    }
                                }

                                $record->update(['agency_id' => $data['agency_id']]);
                                $transferred++;
                            }

                            if ($transferred > 0) {
                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title('Cotizadores transferidos')
                                    ->body("{$transferred} cotizador(es) fueron transferidos a '{$agency->name}'." .
                                           ($skipped > 0 ? " {$skipped} fueron omitidos por límite de cuota." : ""))
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->warning()
                                    ->title('No se pudo transferir')
                                    ->body("La agencia '{$agency->name}' ha alcanzado su límite de cotizadores.")
                                    ->send();
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->visible(fn () => auth()->user()->isSuperAdmin()),

                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No hay cotizadores')
            ->emptyStateDescription('Comienza creando tu primer cotizador')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Crear Cotizador'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCotizadors::route('/'),
            'create' => Pages\CreateCotizador::route('/create'),
            'edit' => Pages\EditCotizador::route('/{record}/edit'),
        ];
    }
}