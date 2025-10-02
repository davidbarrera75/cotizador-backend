<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CotizacionResource\Pages;
use App\Models\Cotizacion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CotizacionResource extends Resource
{
    protected static ?string $model = Cotizacion::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Cotizaciones';
    protected static ?string $navigationGroup = 'CRM';
    protected static ?int $navigationSort = 1;
    
    public static function getEloquentQuery(): Builder
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
        return $form->schema([
            Forms\Components\Section::make('Información de la Cotización')
                ->schema([
                    Forms\Components\Select::make('cotizador_id')
                        ->label('Cotizador')
                        ->relationship('cotizador', 'hotel_name')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->reactive(),

                    Forms\Components\TextInput::make('plan_selected')
                        ->label('Plan Seleccionado')
                        ->required()
                        ->placeholder('Ej: Plan Básico'),

                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('num_adultos')
                                ->label('Adultos')
                                ->numeric()
                                ->required()
                                ->default(2)
                                ->minValue(0),

                            Forms\Components\TextInput::make('num_ninos')
                                ->label('Niños')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),

                            Forms\Components\TextInput::make('acomodacion')
                                ->label('Acomodación')
                                ->required()
                                ->placeholder('Ej: Doble'),
                        ]),

                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('precio_base')
                                ->label('Precio Base')
                                ->prefix('$')
                                ->numeric()
                                ->required()
                                ->default(0),

                            Forms\Components\TextInput::make('precio_adicionales')
                                ->label('Adicionales')
                                ->prefix('$')
                                ->numeric()
                                ->default(0),

                            Forms\Components\TextInput::make('precio_total')
                                ->label('Total')
                                ->prefix('$')
                                ->numeric()
                                ->required()
                                ->default(0),
                        ]),

                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('noches_adicionales')
                                ->label('Noches Adicionales')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),

                            Forms\Components\TextInput::make('almuerzos_adicionales')
                                ->label('Almuerzos Adicionales')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),

                            Forms\Components\TextInput::make('cenas_adicionales')
                                ->label('Cenas Adicionales')
                                ->numeric()
                                ->default(0)
                                ->minValue(0),
                        ]),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Datos del Cliente')
                ->schema([
                    Forms\Components\TextInput::make('customer_name')
                        ->label('Nombre'),
                    
                    Forms\Components\TextInput::make('customer_phone')
                        ->label('Teléfono')
                        ->tel(),
                    
                    Forms\Components\TextInput::make('customer_email')
                        ->label('Email')
                        ->email(),
                ])
                ->columns(3),
            
            Forms\Components\Section::make('Seguimiento')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->label('Estado')
                        ->options([
                            'nuevo' => 'Nuevo',
                            'contactado' => 'Contactado',
                            'negociando' => 'Negociando',
                            'cerrado' => 'Cerrado',
                            'perdido' => 'Perdido',
                        ])
                        ->required()
                        ->default('nuevo'),
                    
                    Forms\Components\Textarea::make('notes')
                        ->label('Notas')
                        ->rows(4)
                        ->columnSpanFull(),
                ])
                ->columns(1),
        ]);
    }

    public static function table(Table $table): Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('id')
                ->label('ID')
                ->sortable(),
            
            Tables\Columns\TextColumn::make('customer_name')
                ->label('Nombre Cliente')
                ->searchable()
                ->placeholder('Sin nombre')
                ->sortable(),
            
            Tables\Columns\TextColumn::make('customer_phone')
                ->label('Teléfono')
                ->searchable()
                ->placeholder('Sin teléfono')
                ->copyable()
                ->url(fn (Cotizacion $record): ?string => $record->whatsapp_url)
                ->openUrlInNewTab()
                ->color('success'),
            
            Tables\Columns\TextColumn::make('customer_email')
                ->label('Email')
                ->searchable()
                ->placeholder('Sin email')
                ->copyable(),
            
            Tables\Columns\TextColumn::make('cotizador.hotel_name')
                ->label('Hotel/Tour')
                ->searchable()
                ->sortable()
                ->limit(30),
            
            Tables\Columns\TextColumn::make('plan_selected')
                ->label('Plan')
                ->searchable(),
            
            Tables\Columns\TextColumn::make('num_adultos')
                ->label('Adultos')
                ->alignCenter(),
            
            Tables\Columns\TextColumn::make('num_ninos')
                ->label('Niños')
                ->alignCenter(),
            
            Tables\Columns\TextColumn::make('acomodacion')
                ->label('Acomodación')
                ->limit(20),
            
            Tables\Columns\TextColumn::make('precio_total')
                ->label('Total')
                ->money('COP')
                ->sortable(),
            
            Tables\Columns\SelectColumn::make('status')
                ->label('Estado')
                ->options([
                    'nuevo' => 'Nuevo',
                    'contactado' => 'Contactado',
                    'negociando' => 'Negociando',
                    'cerrado' => 'Cerrado',
                    'perdido' => 'Perdido',
                ]),
            
            Tables\Columns\TextColumn::make('created_at')
                ->label('Fecha')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
        ])
        ->defaultSort('created_at', 'desc')
        ->filters([
            Tables\Filters\SelectFilter::make('status')
                ->label('Estado')
                ->options([
                    'nuevo' => 'Nuevo',
                    'contactado' => 'Contactado',
                    'negociando' => 'Negociando',
                    'cerrado' => 'Cerrado',
                    'perdido' => 'Perdido',
                ]),
            
            Tables\Filters\SelectFilter::make('cotizador_id')
                ->label('Cotizador')
                ->relationship('cotizador', 'hotel_name')
                ->preload(),
        ])
        ->actions([
            Tables\Actions\EditAction::make(),
            Tables\Actions\DeleteAction::make(),
        ])
        ->bulkActions([
            Tables\Actions\BulkActionGroup::make([
                Tables\Actions\DeleteBulkAction::make(),
            ]),
        ]);
}

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCotizacions::route('/'),
            'create' => Pages\CreateCotizacion::route('/create'),
            'edit' => Pages\EditCotizacion::route('/{record}/edit'),
        ];
    }
}