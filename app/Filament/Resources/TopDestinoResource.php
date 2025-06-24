<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TopDestinoResource\Pages;
use App\Filament\Resources\TopDestinoResource\RelationManagers;
use App\Models\Destino;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TopDestinoResource extends Resource
{
    protected static ?string $model = Destino::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Destinos TOP';

    protected static ?string $modelLabel = 'Destino TOP';

    protected static ?string $pluralModelLabel = 'Destinos TOP';

    protected static ?string $navigationGroup = 'Gestión de Destinos';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Destino TOP')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Se genera automáticamente si se deja vacío'),
                        
                        Forms\Components\Textarea::make('short_description')
                            ->label('Descripción Corta')
                            ->maxLength(500)
                            ->rows(3)
                            ->required(),
                        
                        Forms\Components\RichEditor::make('description')
                            ->label('Descripción Completa')
                            ->columnSpanFull(),
                        
                        Forms\Components\Select::make('region_id')
                            ->label('Región')
                            ->relationship('region', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        
                        Forms\Components\Select::make('status')
                            ->label('Estado')
                            ->options([
                                'draft' => 'Borrador',
                                'pending_review' => 'Pendiente de Revisión',
                                'published' => 'Publicado',
                            ])
                            ->default('published')
                            ->required(),
                        
                        Forms\Components\Toggle::make('is_top')
                            ->label('Destino TOP')
                            ->helperText('Activa esta opción para marcar este destino como destacado')
                            ->default(true)
                            ->required(),
                        
                        Forms\Components\Toggle::make('is_featured')
                            ->label('Destino Destacado')
                            ->helperText('Activa esta opción para marcar este destino como destacado general'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Información de Contacto')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Dirección')
                            ->maxLength(255)
                            ->required(),
                        
                        Forms\Components\TextInput::make('phone')
                            ->label('Teléfono')
                            ->tel()
                            ->maxLength(20),
                        
                        Forms\Components\TextInput::make('whatsapp')
                            ->label('WhatsApp')
                            ->tel()
                            ->maxLength(20),
                        
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('website')
                            ->label('Sitio Web')
                            ->url()
                            ->maxLength(255),
                    ])->columns(2),
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
                
                Tables\Columns\TextColumn::make('region.name')
                    ->label('Región')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('short_description')
                    ->label('Descripción')
                    ->limit(50)
                    ->searchable(),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Estado')
                    ->colors([
                        'warning' => 'draft',
                        'info' => 'pending_review',
                        'success' => 'published',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'pending_review' => 'Pendiente',
                        'published' => 'Publicado',
                    }),
                
                Tables\Columns\IconColumn::make('is_top')
                    ->label('TOP')
                    ->boolean()
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-star')
                    ->trueColor('warning')
                    ->falseColor('gray'),
                
                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Destacado')
                    ->boolean()
                    ->trueIcon('heroicon-o-heart')
                    ->falseIcon('heroicon-o-heart')
                    ->trueColor('danger')
                    ->falseColor('gray'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'pending_review' => 'Pendiente de Revisión',
                        'published' => 'Publicado',
                    ]),
                
                Tables\Filters\SelectFilter::make('region')
                    ->label('Región')
                    ->relationship('region', 'name'),
                
                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Destacado'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_top')
                    ->label('Cambiar TOP')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->action(function (Destino $record, Tables\Actions\Action $action) {
                        $nuevoValor = !$record->is_top;
                        $record->update(['is_top' => $nuevoValor]);
                        $mensaje = $nuevoValor
                            ? 'Destino agregado a la lista TOP correctamente.'
                            : 'Destino removido de la lista TOP. Ya no aparecerá aquí.';
                        $action->successNotificationTitle($mensaje);
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Cambiar estado TOP')
                    ->modalDescription('¿Estás seguro de que quieres cambiar el estado TOP de este destino?')
                    ->modalSubmitActionLabel('Confirmar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_as_top')
                        ->label('Marcar como TOP')
                        ->icon('heroicon-o-star')
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
                    
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListTopDestinos::route('/'),
            'create' => Pages\CreateTopDestino::route('/create'),
            'edit' => Pages\EditTopDestino::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('is_top', true);
    }
}
