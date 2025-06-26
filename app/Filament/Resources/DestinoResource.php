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
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;
use Filament\Forms\Set;

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
                Tabs::make('Destino')
                    ->tabs([
                        Tabs\Tab::make('Información General')
                            ->schema([
                                Section::make('Detalles Principales')
                                    ->schema([
                                        Select::make('user_id')
                                            ->relationship('user', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->label('Proveedor'),

                                        Select::make('region_id')
                                            ->relationship('region', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->label('Región'),

                                        TextInput::make('name')
                                            ->required()
                                            ->maxLength(255)
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn(string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null)
                                            ->label('Nombre del Destino'),

                                        TextInput::make('slug')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(Destino::class, 'slug', ignoreRecord: true),

                                        Select::make('status')
                                            ->options([
                                                'draft' => 'Borrador',
                                                'pending_review' => 'Pendiente de Revisión',
                                                'published' => 'Publicado',
                                                'rejected' => 'Rechazado',
                                            ])
                                            ->required()
                                            ->default('draft')
                                            ->label('Estado'),
                                        
                                        Toggle::make('is_featured')
                                            ->required()
                                            ->label('¿Es Destacado?')
                                            ->helperText('Los destinos destacados aparecen en secciones especiales'),
                                        
                                        Toggle::make('is_top')
                                            ->required()
                                            ->label('¿Es Destino TOP?')
                                            ->helperText('Los destinos TOP aparecen en posiciones destacadas de la portada')
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                if ($state) {
                                                    // Verificar criterios automáticos
                                                    $rating = $get('average_rating') ?? 0;
                                                    $favorites = $get('favorite_count') ?? 0;
                                                    $visits = $get('visit_count') ?? 0;
                                                    
                                                    if ($rating < 4.5 || $favorites < 50 || $visits < 500) {
                                                        $set('is_top', false);
                                                    }
                                                }
                                            }),
                                    ])
                                    ->columns(2),

                                Section::make('Descripciones')
                                    ->schema([
                                        Textarea::make('short_description')
                                            ->required()
                                            ->maxLength(255)
                                            ->label('Descripción Corta')
                                            ->columnSpanFull(),

                                        RichEditor::make('description')
                                            ->required()
                                            ->label('Descripción Completa')
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Clasificación')
                                    ->schema([
                                        Select::make('categorias')
                                            ->multiple()
                                            ->relationship('categorias', 'name')
                                            ->preload()
                                            ->required()
                                            ->label('Categorías'),

                                        Select::make('caracteristicas')
                                            ->multiple()
                                            ->relationship('caracteristicas', 'nombre')
                                            ->preload()
                                            ->searchable()
                                            ->label('Características'),
                                    ])->columns(2),
                            ]),

                        Tabs\Tab::make('Ubicación y Contacto')
                            ->schema([
                                Section::make('Ubicación')
                                    ->schema([
                                        TextInput::make('address')
                                            ->label('Dirección')
                                            ->maxLength(255),
                                        TextInput::make('ubicacion_referencia')
                                            ->label('Referencia de Ubicación')
                                            ->maxLength(255),
                                        LeafletMapPicker::make('location')
                                            ->label('Ubicación en el mapa')
                                            ->defaultZoom(8)
                                            ->defaultLocation([20.1278, -98.7342])
                                            ->columnSpanFull()
                                            ->required(),
                                    ])
                                    ->columns(2),

                                Section::make('Información de Contacto')
                                    ->schema([
                                        TextInput::make('phone')
                                            ->tel()
                                            ->maxLength(255)
                                            ->label('Teléfono'),
                                        TextInput::make('whatsapp')
                                            ->tel()
                                            ->maxLength(255),
                                        TextInput::make('email')
                                            ->email()
                                            ->maxLength(255),
                                        TextInput::make('website')
                                            ->url()
                                            ->maxLength(255)
                                            ->label('Sitio Web'),
                                    ])
                                    ->columns(2),
                            ]),

                        Tabs\Tab::make('SEO y Metadatos')
                            ->schema([
                                Section::make('Metadatos SEO')
                                    ->description('Configuración para optimización en motores de búsqueda')
                                    ->schema([
                                        TextInput::make('titulo_seo')
                                            ->label('Título SEO')
                                            ->maxLength(60)
                                            ->helperText('Título optimizado para SEO (máximo 60 caracteres)')
                                            ->placeholder('Ej: Pueblos Mágicos de Hidalgo - Turismo y Aventura'),

                                        Textarea::make('descripcion_meta')
                                            ->label('Descripción Meta')
                                            ->maxLength(160)
                                            ->rows(3)
                                            ->helperText('Descripción que aparece en los resultados de búsqueda (máximo 160 caracteres)')
                                            ->placeholder('Descubre los pueblos mágicos de Hidalgo con sus tradiciones, gastronomía y paisajes únicos.'),

                                        TextInput::make('keywords')
                                            ->label('Palabras Clave')
                                            ->maxLength(255)
                                            ->helperText('Palabras clave separadas por comas')
                                            ->placeholder('pueblos mágicos, hidalgo, turismo, gastronomía, aventura'),

                                        FileUpload::make('open_graph_image')
                                            ->label('Imagen Open Graph')
                                            ->image()
                                            ->imageEditor()
                                            ->imageCropAspectRatio('1.91:1')
                                            ->imageResizeTargetWidth('1200')
                                            ->imageResizeTargetHeight('630')
                                            ->maxSize(2048) // 2MB
                                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                            ->helperText('Imagen que aparece cuando se comparte en redes sociales (1200x630px)')
                                            ->columnSpanFull(),

                                        Toggle::make('indexar_seo')
                                            ->label('¿Permitir indexación?')
                                            ->helperText('Controla si los motores de búsqueda pueden indexar esta página')
                                            ->default(true),
                                    ])
                                    ->columns(2),

                                Section::make('Estadísticas')
                                    ->description('Información sobre el rendimiento del destino')
                                    ->schema([
                                        Placeholder::make('average_rating')
                                            ->label('Rating Promedio')
                                            ->content(fn (Get $get) => number_format($get('average_rating') ?? 0, 1) . ' / 5.0'),

                                        Placeholder::make('reviews_count')
                                            ->label('Número de Reseñas')
                                            ->content(fn (Get $get) => $get('reviews_count') ?? 0),

                                        Placeholder::make('visit_count')
                                            ->label('Visitas')
                                            ->content(fn (Get $get) => number_format($get('visit_count') ?? 0)),

                                        Placeholder::make('favorite_count')
                                            ->label('Favoritos')
                                            ->content(fn (Get $get) => number_format($get('favorite_count') ?? 0)),
                                    ])
                                    ->columns(2)
                                    ->collapsible()
                                    ->collapsed(),
                            ]),
                    ])
                    ->columnSpanFull(),
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
                Tables\Columns\IconColumn::make('is_top')
                    ->boolean()
                    ->label('TOP')
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('average_rating')
                    ->label('Rating')
                    ->numeric(1)
                    ->sortable(),
                Tables\Columns\TextColumn::make('visit_count')
                    ->label('Visitas')
                    ->numeric()
                    ->sortable(),
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
                Tables\Filters\TernaryFilter::make('is_top')
                    ->label('Destinos TOP')
                    ->placeholder('Todos los destinos')
                    ->trueLabel('Solo TOP')
                    ->falseLabel('No TOP'),
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Destinos Destacados')
                    ->placeholder('Todos los destinos')
                    ->trueLabel('Solo destacados')
                    ->falseLabel('No destacados'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_top')
                    ->label('Cambiar TOP')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->action(function (Destino $record) {
                        $record->update(['is_top' => !$record->is_top]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('¿Cambiar estado TOP?')
                    ->modalDescription(fn (Destino $record) => $record->is_top 
                        ? '¿Quitar este destino de la lista TOP?' 
                        : '¿Agregar este destino a la lista TOP?')
                    ->modalSubmitActionLabel('Sí, cambiar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('mark_as_top')
                        ->label('Marcar como TOP')
                        ->icon('heroicon-o-star')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_top' => true]);
                            });
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('remove_from_top')
                        ->label('Quitar de TOP')
                        ->icon('heroicon-o-star')
                        ->color('gray')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_top' => false]);
                            });
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
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