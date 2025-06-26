<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HomeConfigResource\Pages;
use App\Models\HomeConfig;
use App\Models\Destino;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Get;
use Filament\Forms\Set;

class HomeConfigResource extends Resource
{
    protected static ?string $model = HomeConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $navigationGroup = 'Configuración del Sitio';
    protected static ?string $modelLabel = 'Configuración de Portada';
    protected static ?string $pluralModelLabel = 'Configuraciones de Portada';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Configuración del Hero')
                    ->description('Configuración principal de la sección hero de la portada')
                    ->schema([
                        FileUpload::make('hero_image_path')
                            ->label('Imagen de Fondo del Hero')
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('16:9')
                            ->imageResizeTargetWidth('1920')
                            ->imageResizeTargetHeight('1080')
                            ->maxSize(5120) // 5MB
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Recomendado: 1920x1080px, máximo 5MB. Formatos: JPG, PNG, WebP')
                            ->columnSpanFull(),

                        TextInput::make('hero_title')
                            ->label('Título Principal')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Descubre Hidalgo')
                            ->helperText('Título principal que aparecerá en el hero'),

                        RichEditor::make('hero_subtitle')
                            ->label('Subtítulo')
                            ->required()
                            ->maxLength(500)
                            ->placeholder('Ej: Tierra de aventura y tradición')
                            ->helperText('Subtítulo descriptivo del hero. Soporta formato HTML básico')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'link',
                                'bulletList',
                                'orderedList',
                            ]),

                        TextInput::make('search_placeholder')
                            ->label('Placeholder del Buscador')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Busca destinos, actividades...')
                            ->helperText('Texto que aparecerá en el campo de búsqueda'),

                        Toggle::make('is_active')
                            ->label('¿Configuración Activa?')
                            ->helperText('Solo una configuración puede estar activa a la vez')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Secciones Destacadas')
                    ->description('Configura las secciones visuales que aparecerán en la portada')
                    ->schema([
                        Repeater::make('featured_sections')
                            ->label('Secciones')
                            ->schema([
                                Hidden::make('order')
                                    ->default(fn (Get $get, $context) => $get('../../featured_sections') ? count($get('../../featured_sections')) + 1 : 1),

                                TextInput::make('slug')
                                    ->label('Slug de la Sección')
                                    ->required()
                                    ->maxLength(100)
                                    ->placeholder('Ej: pueblos-magicos')
                                    ->helperText('Identificador único para la sección (sin espacios ni caracteres especiales)')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Set $set, $state) => $set('slug', Str::slug($state))),

                                TextInput::make('title')
                                    ->label('Título de la Sección')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Ej: Pueblos Mágicos'),

                                Textarea::make('subtitle')
                                    ->label('Subtítulo de la Sección')
                                    ->required()
                                    ->maxLength(500)
                                    ->placeholder('Ej: Descubre la magia de nuestros pueblos')
                                    ->rows(2),

                                FileUpload::make('image')
                                    ->label('Imagen Representativa')
                                    ->image()
                                    ->imageEditor()
                                    ->imageCropAspectRatio('4:3')
                                    ->imageResizeTargetWidth('800')
                                    ->imageResizeTargetHeight('600')
                                    ->maxSize(2048) // 2MB
                                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                                    ->helperText('Recomendado: 800x600px, máximo 2MB'),

                                Select::make('destino_ids')
                                    ->label('Destinos de la Sección')
                                    ->multiple()
                                    ->options(Destino::published()->pluck('name', 'id'))
                                    ->searchable()
                                    ->preload()
                                    ->helperText('Selecciona los destinos que aparecerán en esta sección')
                                    ->columnSpanFull(),

                                ColorPicker::make('accent_color')
                                    ->label('Color de Acento')
                                    ->helperText('Color personalizado para la sección (opcional)'),

                                KeyValue::make('metadata')
                                    ->label('Metadatos Adicionales')
                                    ->keyLabel('Clave')
                                    ->valueLabel('Valor')
                                    ->helperText('Información adicional para la sección (opcional)')
                                    ->columnSpanFull(),
                            ])
                            ->defaultItems(0)
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => $state['title'] ?? null)
                            ->columnSpanFull()
                            ->addActionLabel('Agregar Sección')
                            ->cloneable()
                            ->deletable()
                            ->helperText('Configura las secciones que aparecerán en la portada. Puedes reordenarlas arrastrando.')
                    ]),

                Section::make('Configuración Avanzada')
                    ->description('Opciones adicionales de configuración')
                    ->schema([
                        KeyValue::make('advanced_settings')
                            ->label('Configuración Avanzada')
                            ->keyLabel('Clave')
                            ->valueLabel('Valor')
                            ->helperText('Configuraciones adicionales en formato clave-valor')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('hero_title')
                    ->label('Título del Hero')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('hero_subtitle')
                    ->label('Subtítulo')
                    ->limit(80)
                    ->html()
                    ->searchable(),

                Tables\Columns\ImageColumn::make('hero_image_path')
                    ->label('Imagen de Fondo')
                    ->circular()
                    ->size(60),

                Tables\Columns\TextColumn::make('featured_sections')
                    ->label('Secciones')
                    ->formatStateUsing(fn ($state) => $state ? count($state) . ' secciones' : 'Sin secciones')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Última Actualización')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todas las configuraciones')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('activate')
                    ->label('Activar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('¿Activar esta configuración?')
                    ->modalDescription('Esta acción desactivará todas las demás configuraciones y activará esta.')
                    ->modalSubmitActionLabel('Sí, activar')
                    ->action(fn (HomeConfig $record) => $record->activate())
                    ->visible(fn (HomeConfig $record) => !$record->is_active),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListHomeConfigs::route('/'),
            'create' => Pages\CreateHomeConfig::route('/create'),
            'edit' => Pages\EditHomeConfig::route('/{record}/edit'),
            'view' => Pages\ViewHomeConfig::route('/{record}'),
        ];
    }
} 