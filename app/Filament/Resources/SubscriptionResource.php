<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Gestión de Suscripciones';

    protected static ?string $navigationLabel = 'Suscripciones';

    protected static ?string $modelLabel = 'Suscripción';

    protected static ?string $pluralModelLabel = 'Suscripciones';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Información de Suscripción')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->label('Usuario'),

                        Forms\Components\Select::make('plan_type')
                            ->options([
                                Subscription::PLAN_BASIC => 'Plan Básico',
                                Subscription::PLAN_PREMIUM => 'Plan Premium',
                                Subscription::PLAN_ENTERPRISE => 'Plan Enterprise',
                            ])
                            ->required()
                            ->label('Tipo de Plan')
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $config = Subscription::getPlanConfig($state);
                                    $set('amount', $config['price']);
                                }
                            }),

                        Forms\Components\Select::make('status')
                            ->options([
                                Subscription::STATUS_ACTIVE => 'Activa',
                                Subscription::STATUS_CANCELLED => 'Cancelada',
                                Subscription::STATUS_EXPIRED => 'Expirada',
                                Subscription::STATUS_PENDING => 'Pendiente',
                            ])
                            ->required()
                            ->label('Estado'),

                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->label('Monto'),

                        Forms\Components\TextInput::make('currency')
                            ->default('MXN')
                            ->maxLength(3)
                            ->label('Moneda'),
                    ])->columns(2),

                Forms\Components\Section::make('Fechas y Ciclo de Facturación')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->label('Fecha de Inicio'),

                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->label('Fecha de Fin'),

                        Forms\Components\DatePicker::make('next_billing_date')
                            ->label('Próxima Facturación'),

                        Forms\Components\Select::make('billing_cycle')
                            ->options([
                                Subscription::CYCLE_MONTHLY => 'Mensual',
                                Subscription::CYCLE_QUARTERLY => 'Trimestral',
                                Subscription::CYCLE_YEARLY => 'Anual',
                            ])
                            ->required()
                            ->label('Ciclo de Facturación'),

                        Forms\Components\Toggle::make('auto_renew')
                            ->label('Renovación Automática')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('Información de Pago')
                    ->schema([
                        Forms\Components\Select::make('payment_method')
                            ->options([
                                'credit_card' => 'Tarjeta de Crédito',
                                'debit_card' => 'Tarjeta de Débito',
                                'paypal' => 'PayPal',
                                'bank_transfer' => 'Transferencia Bancaria',
                                'cash' => 'Efectivo',
                            ])
                            ->label('Método de Pago'),

                        Forms\Components\Select::make('payment_status')
                            ->options([
                                Subscription::PAYMENT_PENDING => 'Pendiente',
                                Subscription::PAYMENT_COMPLETED => 'Completado',
                                Subscription::PAYMENT_FAILED => 'Fallido',
                            ])
                            ->default(Subscription::PAYMENT_PENDING)
                            ->label('Estado del Pago'),

                        Forms\Components\TextInput::make('transaction_id')
                            ->label('ID de Transacción')
                            ->maxLength(100),
                    ])->columns(3),

                Forms\Components\Section::make('Información Adicional')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->maxLength(1000),

                        Forms\Components\KeyValue::make('features')
                            ->label('Características del Plan')
                            ->keyLabel('Característica')
                            ->valueLabel('Valor')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Usuario'),

                Tables\Columns\TextColumn::make('plan_type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Subscription::PLAN_BASIC => 'gray',
                        Subscription::PLAN_PREMIUM => 'warning',
                        Subscription::PLAN_ENTERPRISE => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Subscription::PLAN_BASIC => 'Básico',
                        Subscription::PLAN_PREMIUM => 'Premium',
                        Subscription::PLAN_ENTERPRISE => 'Enterprise',
                        default => $state,
                    })
                    ->label('Plan'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Subscription::STATUS_ACTIVE => 'success',
                        Subscription::STATUS_CANCELLED => 'danger',
                        Subscription::STATUS_EXPIRED => 'warning',
                        Subscription::STATUS_PENDING => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Subscription::STATUS_ACTIVE => 'Activa',
                        Subscription::STATUS_CANCELLED => 'Cancelada',
                        Subscription::STATUS_EXPIRED => 'Expirada',
                        Subscription::STATUS_PENDING => 'Pendiente',
                        default => $state,
                    })
                    ->label('Estado'),

                Tables\Columns\TextColumn::make('amount')
                    ->money('MXN')
                    ->sortable()
                    ->label('Monto'),

                Tables\Columns\TextColumn::make('billing_cycle')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Subscription::CYCLE_MONTHLY => 'Mensual',
                        Subscription::CYCLE_QUARTERLY => 'Trimestral',
                        Subscription::CYCLE_YEARLY => 'Anual',
                        default => $state,
                    })
                    ->label('Ciclo'),

                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->label('Inicio'),

                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->label('Fin')
                    ->color(fn (Subscription $record): string => 
                        $record->isExpired() ? 'danger' : 
                        ($record->isExpiringSoon() ? 'warning' : 'success')
                    ),

                Tables\Columns\IconColumn::make('auto_renew')
                    ->boolean()
                    ->label('Auto Renovar'),

                Tables\Columns\TextColumn::make('payment_status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Subscription::PAYMENT_COMPLETED => 'success',
                        Subscription::PAYMENT_PENDING => 'warning',
                        Subscription::PAYMENT_FAILED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Subscription::PAYMENT_COMPLETED => 'Completado',
                        Subscription::PAYMENT_PENDING => 'Pendiente',
                        Subscription::PAYMENT_FAILED => 'Fallido',
                        default => $state,
                    })
                    ->label('Pago'),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Creado'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('plan_type')
                    ->options([
                        Subscription::PLAN_BASIC => 'Plan Básico',
                        Subscription::PLAN_PREMIUM => 'Plan Premium',
                        Subscription::PLAN_ENTERPRISE => 'Plan Enterprise',
                    ])
                    ->label('Tipo de Plan'),

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        Subscription::STATUS_ACTIVE => 'Activa',
                        Subscription::STATUS_CANCELLED => 'Cancelada',
                        Subscription::STATUS_EXPIRED => 'Expirada',
                        Subscription::STATUS_PENDING => 'Pendiente',
                    ])
                    ->label('Estado'),

                Tables\Filters\SelectFilter::make('billing_cycle')
                    ->options([
                        Subscription::CYCLE_MONTHLY => 'Mensual',
                        Subscription::CYCLE_QUARTERLY => 'Trimestral',
                        Subscription::CYCLE_YEARLY => 'Anual',
                    ])
                    ->label('Ciclo de Facturación'),

                Tables\Filters\TernaryFilter::make('auto_renew')
                    ->label('Renovación Automática'),

                Tables\Filters\Filter::make('expiring_soon')
                    ->query(fn (Builder $query): Builder => $query->expiringSoon())
                    ->label('Próximas a Expirar'),

                Tables\Filters\Filter::make('expired')
                    ->query(fn (Builder $query): Builder => $query->expired())
                    ->label('Expiradas'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('cancel')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (Subscription $record) => $record->cancel())
                    ->visible(fn (Subscription $record) => $record->isActive())
                    ->label('Cancelar'),

                Tables\Actions\Action::make('renew')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (Subscription $record) => $record->renew())
                    ->visible(fn (Subscription $record) => $record->status === Subscription::STATUS_CANCELLED)
                    ->label('Renovar'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('cancel')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn (Collection $records) => $records->each->cancel())
                        ->label('Cancelar Seleccionadas'),
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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'view' => Pages\ViewSubscription::route('/{record}'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user');
    }
} 