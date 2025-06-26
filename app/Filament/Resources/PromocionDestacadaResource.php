<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PromocionDestacadaResource\Pages;
use App\Models\PromocionDestacada;
use App\Models\Destino;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Grid;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Carbon\Carbon;

class PromocionDestacadaResource extends Resource
{
    protected static ?string $model = PromocionDestacada::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    protected static ?string $navigationGroup = 'Gestión de Contenido';
    protected static ?string $modelLabel = 'Promoción Destacada';
    protected static ?string $pluralModelLabel = 'Promociones Destacadas';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de la Promoción')
                    ->description('Configura los detalles principales de la promoción destacada')
                    ->schema([
                        TextInput::make('titulo')
                            ->label('Título de la Promoción')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Ej: Verano en la Huasteca')
                            ->helperText('Título atractivo que aparecerá en la portada'),

                        RichEditor::make('descripcion')
                            ->label('Descripción')
                            ->required()
                            ->maxLength(1000)
                            ->placeholder('Describe los beneficios y detalles de la promoción')
                            ->helperText('Descripción detallada que aparecerá junto al título')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'link',
                                'bulletList',
                                'orderedList',
                            ])
                            ->columnSpanFull(),

                        FileUpload::make('imagen')
                            ->label('Imagen de la Promoción')
                            ->image()
                            ->imageEditor()
                            ->imageCropAspectRatio('2:1')
                            ->imageResizeTargetWidth('800')
                            ->imageResizeTargetHeight('400')
                            ->maxSize(2048) // 2MB
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->helperText('Imagen representativa de la promoción (800x400px recomendado)')
                            ->columnSpanFull(),

                        Toggle::make('is_active')
                            ->label('¿Promoción Activa?')
                            ->helperText('Solo las promociones activas aparecerán en la portada')
                            ->default(true),
                    ])
                    ->columns(2),

                Section::make('Fechas de Vigencia')
                    ->description('Define cuándo estará disponible la promoción')
                    ->schema([
                        DateTimePicker::make('fecha_inicio')
                            ->label('Fecha de Inicio')
                            ->required()
                            ->default(now())
                            ->helperText('Cuándo comenzará a mostrarse la promoción')
                            ->afterStateUpdated(function (Set $set, Get $get) {
                                $fechaInicio = $get('fecha_inicio');
                                $fechaFin = $get('fecha_fin');
                                
                                // Si la fecha de fin es anterior a la de inicio, ajustar
                                if ($fechaInicio && $fechaFin && $fechaFin <= $fechaInicio) {
                                    $set('fecha_fin', Carbon::parse($fechaInicio)->addDays(7));
                                }
                            }),

                        DateTimePicker::make('fecha_fin')
                            ->label('Fecha de Fin')
                            ->required()
                            ->default(now()->addDays(7))
                            ->helperText('Cuándo dejará de mostrarse la promoción')
                            ->rules([
                                'after:fecha_inicio'
                            ]),
                    ])
                    ->columns(2),

                Section::make('Destinos Relacionados')
                    ->description('Asocia esta promoción con destinos específicos (opcional)')
                    ->schema([
                        Select::make('destinos')
                            ->label('Destinos de la Promoción')
                            ->multiple()
                            ->relationship('destinos', 'name', function ($query) {
                                return $query->where('status', 'published');
                            })
                            ->searchable()
                            ->helperText('Selecciona los destinos que se promocionarán. Si no seleccionas ninguno, la promoción será general.')
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),

                Section::make('Estado de la Promoción')
                    ->description('Información sobre el estado actual')
                    ->schema([
                        TextInput::make('estado_actual')
                            ->label('Estado Actual')
                            ->disabled()
                            ->default(function (Get $get, $record = null) {
                                // Si tenemos un record, usar el accessor del modelo
                                if ($record) {
                                    return $record->estado;
                                }
                                
                                // Si no, calcular basado en los valores del formulario
                                $fechaInicio = $get('fecha_inicio');
                                $fechaFin = $get('fecha_fin');
                                $isActive = $get('is_active');
                                
                                if (!$fechaInicio || !$fechaFin) {
                                    return 'Pendiente de configuración';
                                }
                                
                                $now = now();
                                $inicio = Carbon::parse($fechaInicio);
                                $fin = Carbon::parse($fechaFin);
                                
                                if (!$isActive) {
                                    return 'Inactiva';
                                } elseif ($now < $inicio) {
                                    return 'Futura (inicia ' . $inicio->diffForHumans() . ')';
                                } elseif ($now >= $inicio && $now <= $fin) {
                                    return 'Vigente (termina ' . $fin->diffForHumans() . ')';
                                } else {
                                    return 'Expirada';
                                }
                            })
                            ->helperText('El estado se calcula automáticamente según las fechas y el estado activo'),
                    ])
                    ->collapsible()
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                Tables\Columns\TextColumn::make('descripcion')
                    ->label('Descripción')
                    ->limit(80)
                    ->html()
                    ->searchable(),

                Tables\Columns\ImageColumn::make('imagen')
                    ->label('Imagen')
                    ->circular()
                    ->size(60),

                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fecha_fin')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(function ($record) {
                        return $record->estado;
                    }),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),

                Tables\Columns\TextColumn::make('destinos_count')
                    ->label('Destinos')
                    ->formatStateUsing(function ($record) {
                        return $record->destinos()->count();
                    })
                    ->badge(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->label('Estado')
                    ->options([
                        'vigente' => 'Vigente',
                        'futura' => 'Futura',
                        'expirada' => 'Expirada',
                    ])
                    ->query(function ($query, array $data) {
                        if (!empty($data['values'])) {
                            $now = now();
                            foreach ($data['values'] as $estado) {
                                switch ($estado) {
                                    case 'vigente':
                                        $query->orWhere(function ($q) use ($now) {
                                            $q->where('is_active', true)
                                              ->where('fecha_inicio', '<=', $now)
                                              ->where('fecha_fin', '>=', $now);
                                        });
                                        break;
                                    case 'futura':
                                        $query->orWhere(function ($q) use ($now) {
                                            $q->where('is_active', true)
                                              ->where('fecha_inicio', '>', $now);
                                        });
                                        break;
                                    case 'expirada':
                                        $query->orWhere('fecha_fin', '<', $now);
                                        break;
                                }
                            }
                        }
                        return $query;
                    }),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Solo Activas')
                    ->placeholder('Todas las promociones')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas'),

                Tables\Filters\Filter::make('vigentes_hoy')
                    ->label('Vigentes Hoy')
                    ->query(fn ($query) => $query->vigentes()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_active')
                    ->label('Cambiar Estado')
                    ->icon('heroicon-o-power')
                    ->action(function (PromocionDestacada $record) {
                        $record->update(['is_active' => !$record->is_active]);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('¿Cambiar estado de la promoción?')
                    ->modalDescription(fn (PromocionDestacada $record) => $record->is_active 
                        ? '¿Desactivar esta promoción?' 
                        : '¿Activar esta promoción?')
                    ->modalSubmitActionLabel('Sí, cambiar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activar Promociones')
                        ->icon('heroicon-o-check-circle')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => true]);
                            });
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Desactivar Promociones')
                        ->icon('heroicon-o-x-circle')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => false]);
                            });
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListPromocionDestacadas::route('/'),
            'create' => Pages\CreatePromocionDestacada::route('/create'),
            'edit' => Pages\EditPromocionDestacada::route('/{record}/edit'),
            'view' => Pages\ViewPromocionDestacada::route('/{record}'),
        ];
    }
} 