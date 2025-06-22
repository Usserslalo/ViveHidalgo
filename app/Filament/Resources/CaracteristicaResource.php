<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CaracteristicaResource\Pages;
use App\Models\Caracteristica;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CaracteristicaResource extends Resource
{
    protected static ?string $model = Caracteristica::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Content Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->unique(Caracteristica::class, 'slug', ignoreRecord: true),

                Forms\Components\Select::make('tipo')
                    ->options([
                        'amenidad' => 'Amenidad',
                        'actividad' => 'Actividad',
                        'cultural' => 'Cultural',
                        'natural' => 'Natural',
                        'especial' => 'Especial',
                        'alojamiento' => 'Alojamiento',
                        'general' => 'General',
                    ])
                    ->required()
                    ->default('general'),

                Forms\Components\TextInput::make('icono')
                    ->maxLength(255)
                    ->helperText('Ejemplo: fas fa-wifi, fas fa-parking'),

                Forms\Components\Textarea::make('descripcion')
                    ->maxLength(65535)
                    ->columnSpanFull(),

                Forms\Components\Toggle::make('activo')
                    ->required()
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->colors([
                        'primary' => 'amenidad',
                        'success' => 'actividad',
                        'warning' => 'cultural',
                        'info' => 'natural',
                        'danger' => 'especial',
                        'secondary' => 'alojamiento',
                    ])
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('icono')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('activo')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'amenidad' => 'Amenidad',
                        'actividad' => 'Actividad',
                        'cultural' => 'Cultural',
                        'natural' => 'Natural',
                        'especial' => 'Especial',
                        'alojamiento' => 'Alojamiento',
                        'general' => 'General',
                    ]),
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activas')
                    ->falseLabel('Inactivas'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCaracteristicas::route('/'),
            'create' => Pages\CreateCaracteristica::route('/create'),
            'edit' => Pages\EditCaracteristica::route('/{record}/edit'),
        ];
    }
} 