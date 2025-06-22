<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DestinoResource\Pages;
use App\Models\Destino;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Afsakar\LeafletMapPicker\LeafletMapPicker;

class DestinoResource extends Resource
{
    protected static ?string $model = Destino::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'Gestión de Contenido';
    protected static ?string $modelLabel = 'Destino Turístico';
    protected static ?string $pluralModelLabel = 'Destinos Turísticos';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detalles Principales')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Proveedor'),

                        Forms\Components\Select::make('region_id')
                            ->relationship('region', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Región'),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn(string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null)
                            ->label('Nombre del Destino'),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(Destino::class, 'slug', ignoreRecord: true),

                        Forms\Components\Select::make('status')
                            ->options([
                                'draft' => 'Borrador',
                                'pending_review' => 'Pendiente de Revisión',
                                'published' => 'Publicado',
                                'rejected' => 'Rechazado',
                            ])
                            ->required()
                            ->default('draft')
                            ->label('Estado'),
                        
                        Forms\Components\Toggle::make('is_featured')
                            ->required()
                            ->label('¿Es Destacado?'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Descripciones')
                    ->schema([
                        Forms\Components\Textarea::make('short_description')
                            ->required()
                            ->maxLength(255)
                            ->label('Descripción Corta')
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('description')
                            ->required()
                            ->label('Descripción Completa')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Clasificación')
                    ->schema([
                        Forms\Components\Select::make('categorias')
                            ->multiple()
                            ->relationship('categorias', 'name')
                            ->preload()
                            ->required()
                            ->label('Categorías'),

                        Forms\Components\Select::make('caracteristicas')
                            ->multiple()
                            ->relationship('caracteristicas', 'nombre')
                            ->preload()
                            ->searchable()
                            ->label('Características'),
                    ])->columns(2),

                Forms\Components\Section::make('Ubicación y Contacto')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Dirección')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('ubicacion_referencia')
                            ->label('Referencia de Ubicación')
                            ->maxLength(255),
                        LeafletMapPicker::make('location')
                            ->label('Ubicación en el mapa')
                            ->defaultZoom(8)
                            ->defaultLocation([20.1278, -98.7342])
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\TextInput::make('phone')
                            ->tel()
                            ->maxLength(255)
                            ->label('Teléfono'),
                        Forms\Components\TextInput::make('whatsapp')
                            ->tel()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('website')
                            ->url()
                            ->maxLength(255)
                            ->label('Sitio Web'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->label('Nombre'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('region.name')
                    ->label('Región')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'secondary' => 'draft',
                        'warning' => 'pending_review',
                        'success' => 'published',
                        'danger' => 'rejected',
                    ])
                    ->searchable()
                    ->sortable()
                    ->label('Estado'),
                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Destacado'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Fecha de Creación'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('region')
                    ->relationship('region', 'name')
                    ->label('Región'),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Borrador',
                        'pending_review' => 'Pendiente de Revisión',
                        'published' => 'Publicado',
                        'rejected' => 'Rechazado',
                    ])->label('Estado'),
                Tables\Filters\SelectFilter::make('caracteristicas')
                    ->relationship('caracteristicas', 'nombre')
                    ->label('Características'),
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
            'index' => Pages\ListDestinos::route('/'),
            'create' => Pages\CreateDestino::route('/create'),
            'edit' => Pages\EditDestino::route('/{record}/edit'),
        ];
    }
} 