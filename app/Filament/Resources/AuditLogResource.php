<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Model;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?string $navigationLabel = 'Logs de Auditoría';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Log de Auditoría';

    protected static ?string $pluralModelLabel = 'Logs de Auditoría';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información del Evento')
                    ->schema([
                        Forms\Components\TextInput::make('event_type')
                            ->label('Tipo de Evento')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('description')
                            ->label('Descripción')
                            ->disabled()
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('auditable_type')
                            ->label('Tipo de Modelo')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('auditable_id')
                            ->label('ID del Modelo')
                            ->disabled()
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Información del Usuario')
                    ->schema([
                        Forms\Components\TextInput::make('user.name')
                            ->label('Usuario')
                            ->disabled()
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('ip_address')
                            ->label('Dirección IP')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\TextInput::make('user_agent')
                            ->label('User Agent')
                            ->disabled()
                            ->columnSpan(3),

                        Forms\Components\TextInput::make('url')
                            ->label('URL')
                            ->disabled()
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('method')
                            ->label('Método HTTP')
                            ->disabled()
                            ->columnSpan(1),
                    ])
                    ->columns(3),

                Forms\Components\Section::make('Cambios')
                    ->schema([
                        Forms\Components\KeyValue::make('old_values')
                            ->label('Valores Anteriores')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\KeyValue::make('new_values')
                            ->label('Valores Nuevos')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadatos')
                            ->disabled()
                            ->columnSpan(1),
                    ])
                    ->columns(3)
                    ->collapsible(),

                Forms\Components\Section::make('Información Temporal')
                    ->schema([
                        Forms\Components\DateTimePicker::make('created_at')
                            ->label('Fecha de Creación')
                            ->disabled()
                            ->columnSpan(1),

                        Forms\Components\DateTimePicker::make('updated_at')
                            ->label('Fecha de Actualización')
                            ->disabled()
                            ->columnSpan(1),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('event_type')
                    ->label('Tipo de Evento')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        'login' => 'warning',
                        'logout' => 'gray',
                        default => 'primary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'created' => 'Creado',
                        'updated' => 'Actualizado',
                        'deleted' => 'Eliminado',
                        'login' => 'Login',
                        'logout' => 'Logout',
                        'subscription_created' => 'Suscripción Creada',
                        'subscription_cancelled' => 'Suscripción Cancelada',
                        'review_approved' => 'Reseña Aprobada',
                        'review_rejected' => 'Reseña Rechazada',
                        'promotion_expired' => 'Promoción Expirada',
                        default => $state,
                    })
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('auditable_type')
                    ->label('Modelo')
                    ->formatStateUsing(fn (string $state): string => class_basename($state))
                    ->sortable()
                    ->searchable(),

                Tables\Columns\TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->searchable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->searchable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->label('Tipo de Evento')
                    ->options([
                        'created' => 'Creado',
                        'updated' => 'Actualizado',
                        'deleted' => 'Eliminado',
                        'login' => 'Login',
                        'logout' => 'Logout',
                        'subscription_created' => 'Suscripción Creada',
                        'subscription_cancelled' => 'Suscripción Cancelada',
                        'review_approved' => 'Reseña Aprobada',
                        'review_rejected' => 'Reseña Rechazada',
                        'promotion_expired' => 'Promoción Expirada',
                    ]),

                SelectFilter::make('auditable_type')
                    ->label('Tipo de Modelo')
                    ->options([
                        'App\Models\User' => 'Usuario',
                        'App\Models\Destino' => 'Destino',
                        'App\Models\Promocion' => 'Promoción',
                        'App\Models\Review' => 'Reseña',
                        'App\Models\Subscription' => 'Suscripción',
                    ]),

                Filter::make('created_at')
                    ->form([
                        DatePicker::make('created_from')
                            ->label('Desde'),
                        DatePicker::make('created_until')
                            ->label('Hasta'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([25, 50, 100]);
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
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['user:id,name', 'auditable'])
            ->orderByDesc('created_at');
    }

    public static function canCreate(): bool
    {
        return false; // Los logs de auditoría no se pueden crear manualmente
    }

    public static function canEdit(Model $record): bool
    {
        return false; // Los logs de auditoría no se pueden editar
    }
} 