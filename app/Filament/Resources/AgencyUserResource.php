<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgencyUserResource\Pages;
use App\Models\User;
use App\Models\Agency;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AgencyUserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'Usuarios';
    protected static ?string $navigationGroup = 'Administración';
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make()
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(255),
                    
                    Forms\Components\TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        ->required(fn ($context) => $context === 'create')
                        ->dehydrated(fn ($state) => filled($state))
                        ->minLength(8),
                    
                    Forms\Components\Select::make('agencies')
                        ->label('Agencias')
                        ->multiple()
                        ->relationship('agencies', 'name')
                        ->preload()
                        ->visible(fn () => auth()->user()->isSuperAdmin()),
                    
                    Forms\Components\Toggle::make('is_super_admin')
                        ->label('Super Administrador')
                        ->visible(fn () => auth()->user()->isSuperAdmin()),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('agencies.name')
                    ->label('Agencias')
                    ->badge()
                    ->separator(','),
                
                Tables\Columns\IconColumn::make('is_super_admin')
                    ->label('Super Admin')
                    ->boolean()
                    ->visible(fn () => auth()->user()->isSuperAdmin()),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgencyUsers::route('/'),
            'create' => Pages\CreateAgencyUser::route('/create'),
            'edit' => Pages\EditAgencyUser::route('/{record}/edit'),
        ];
    }
    
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}