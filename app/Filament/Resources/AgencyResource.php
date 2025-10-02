<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgencyResource\Pages;
use App\Models\Agency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AgencyResource extends Resource
{
    protected static ?string $model = Agency::class;
    protected static ?string $navigationIcon = 'heroicon-o-building-office';
    protected static ?string $navigationLabel = 'Agencias';
    protected static ?string $navigationGroup = 'Administración';
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Información de la Agencia')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255)
                        ->alphaDash(),

                    Forms\Components\FileUpload::make('logo')
                        ->label('Logo')
                        ->image()
                        ->disk('public')
                        ->directory('agencies/logos')
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '16:9',
                            '4:3',
                            '1:1',
                        ])
                        ->maxSize(2048)
                        ->helperText('Imagen del logo de la agencia (máx. 2MB)')
                        ->columnSpanFull(),

                    Forms\Components\Select::make('plan')
                        ->label('Plan')
                        ->options([
                            'basic' => 'Básico',
                            'pro' => 'Pro',
                            'enterprise' => 'Enterprise',
                        ])
                        ->required()
                        ->default('basic'),

                    Forms\Components\DateTimePicker::make('expires_at')
                        ->label('Fecha de Expiración')
                        ->required(),

                    Forms\Components\Toggle::make('active')
                        ->label('Activa')
                        ->default(true),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('Límites')
                ->schema([
                    Forms\Components\TextInput::make('max_cotizadores')
                        ->label('Máximo de Cotizadores')
                        ->numeric()
                        ->required()
                        ->default(5),
                    
                    Forms\Components\TextInput::make('max_users')
                        ->label('Máximo de Usuarios')
                        ->numeric()
                        ->required()
                        ->default(1),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('Logo')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(url('/images/default-agency-logo.png')),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('plan')
                    ->label('Plan')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'basic' => 'gray',
                        'pro' => 'success',
                        'enterprise' => 'warning',
                    }),
                
                Tables\Columns\IconColumn::make('active')
                    ->label('Estado')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('cotizadores_count')
                    ->label('Cotizadores')
                    ->counts('cotizadores')
                    ->badge(),
                
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Usuarios')
                    ->counts('users')
                    ->badge(),
                
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expira')
                    ->dateTime('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan')
                    ->options([
                        'basic' => 'Básico',
                        'pro' => 'Pro',
                        'enterprise' => 'Enterprise',
                    ]),
                
                Tables\Filters\TernaryFilter::make('active')
                    ->label('Activa'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgencies::route('/'),
            'create' => Pages\CreateAgency::route('/create'),
            'edit' => Pages\EditAgency::route('/{record}/edit'),
        ];
    }
    
    // Solo visible para super admins
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}