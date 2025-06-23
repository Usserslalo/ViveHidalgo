<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromocionResource\Pages;
use App\Models\Promocion;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PromocionResource extends Resource
{
    protected static ?string $model = Promocion::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Gestión de Contenido';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('destino_id')
                    ->relationship('destino', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('title')
                    ->label('Título')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label('Descripción')
                    ->maxLength(65535)
                    ->columnSpanFull(),
                TextInput::make('code')
                    ->label('Código de Promoción')
                    ->maxLength(255),
                TextInput::make('discount_percentage')
                    ->label('Porcentaje de Descuento (%)')
                    ->numeric()
                    ->maxValue(100),
                DateTimePicker::make('start_date')
                    ->label('Fecha de Inicio')
                    ->required()
                    ->minDate(now()),
                DateTimePicker::make('end_date')
                    ->label('Fecha de Fin')
                    ->required()
                    ->afterOrEqual('start_date'),
                Toggle::make('is_active')
                    ->label('Activa')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('destino.name')
                    ->label('Destino')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label('Inicio')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Fin')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPromocions::route('/'),
            'create' => Pages\CreatePromocion::route('/create'),
            'edit' => Pages\EditPromocion::route('/{record}/edit'),
        ];
    }
} 