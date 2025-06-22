<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Gestión de Contenido';

    protected static ?string $navigationLabel = 'Reseñas';

    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('destino_id')
                    ->relationship('destino', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('rating')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(5)
                    ->label('Calificación'),
                Textarea::make('comment')
                    ->maxLength(1000)
                    ->label('Comentario'),
                Toggle::make('is_approved')
                    ->label('Aprobada')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('destino.name')
                    ->label('Destino')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('rating')
                    ->label('Calificación')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '5' => 'success',
                        '4' => 'success',
                        '3' => 'warning',
                        '2' => 'danger',
                        '1' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('comment')
                    ->label('Comentario')
                    ->limit(50)
                    ->searchable(),
                BooleanColumn::make('is_approved')
                    ->label('Aprobada')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Fecha de Creación')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('is_approved')
                    ->label('Estado de Aprobación')
                    ->options([
                        '1' => 'Aprobadas',
                        '0' => 'Pendientes',
                    ]),
                SelectFilter::make('rating')
                    ->label('Calificación')
                    ->options([
                        '5' => '5 estrellas',
                        '4' => '4 estrellas',
                        '3' => '3 estrellas',
                        '2' => '2 estrellas',
                        '1' => '1 estrella',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Action::make('approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Review $record) => $record->update(['is_approved' => true]))
                    ->visible(fn (Review $record) => !$record->is_approved),
                Action::make('reject')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Review $record) => $record->update(['is_approved' => false]))
                    ->visible(fn (Review $record) => $record->is_approved),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Action::make('approve_selected')
                        ->label('Aprobar Seleccionadas')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_approved' => true]);
                            });
                        }),
                    Action::make('reject_selected')
                        ->label('Rechazar Seleccionadas')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_approved' => false]);
                            });
                        }),
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
            'index' => Pages\ListReviews::route('/'),
            'create' => Pages\CreateReview::route('/create'),
            'edit' => Pages\EditReview::route('/{record}/edit'),
        ];
    }
} 