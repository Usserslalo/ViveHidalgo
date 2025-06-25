<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SubscriptionResource\Pages;
use App\Models\Subscription;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SubscriptionResource extends Resource
{
    protected static ?string $model = Subscription::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Facturación';

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
                            ->label('Usuario')
                            ->helperText('Usuario propietario de la suscripción'),

                        Forms\Components\Select::make('plan_type')
                            ->options([
                                Subscription::PLAN_BASIC => 'Plan Básico',
                                Subscription::PLAN_PREMIUM => 'Plan Premium',
                                Subscription::PLAN_ENTERPRISE => 'Plan Enterprise',
                            ])
                            ->required()
                            ->label('Tipo de Plan')
                            ->live()
                            ->afterStateUpdated(function ($state, callable $set) {
                                if ($state) {
                                    $config = Subscription::getPlanConfig($state);
                                    $set('amount', $config['price']);
                                }
                            })
                            ->helperText('Tipo de plan de suscripción'),

                        Forms\Components\Select::make('status')
                            ->options([
                                Subscription::STATUS_ACTIVE => 'Activa',
                                Subscription::STATUS_CANCELLED => 'Cancelada',
                                Subscription::STATUS_EXPIRED => 'Expirada',
                                Subscription::STATUS_PENDING => 'Pendiente',
                            ])
                            ->required()
                            ->label('Estado')
                            ->helperText('Estado actual de la suscripción'),

                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->placeholder('0.00')
                            ->helperText('Monto de la suscripción')
                            ->label('Monto')
                            ->validationMessages([
                                'min' => 'El monto debe ser mayor o igual a 0',
                                'numeric' => 'El monto debe ser un número válido',
                            ]),

                        Forms\Components\TextInput::make('currency')
                            ->default('mxn')
                            ->maxLength(3)
                            ->placeholder('mxn')
                            ->helperText('Código de moneda (ej: mxn, usd)')
                            ->label('Moneda')
                            ->validationMessages([
                                'max' => 'El código de moneda debe tener máximo 3 caracteres',
                            ]),
                    ])->columns(2),

                Forms\Components\Section::make('Fechas y Ciclo de Facturación')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->required()
                            ->label('Fecha de Inicio')
                            ->helperText('Fecha de inicio de la suscripción'),

                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->label('Fecha de Fin')
                            ->helperText('Fecha de finalización de la suscripción'),

                        Forms\Components\DatePicker::make('next_billing_date')
                            ->label('Próxima Facturación')
                            ->helperText('Fecha de la próxima facturación'),

                        Forms\Components\Select::make('billing_cycle')
                            ->options([
                                Subscription::CYCLE_MONTHLY => 'Mensual',
                                Subscription::CYCLE_QUARTERLY => 'Trimestral',
                                Subscription::CYCLE_YEARLY => 'Anual',
                            ])
                            ->required()
                            ->label('Ciclo de Facturación')
                            ->helperText('Frecuencia de facturación'),

                        Forms\Components\Toggle::make('auto_renew')
                            ->label('Renovación Automática')
                            ->default(true)
                            ->helperText('Renovar automáticamente al vencer'),
                    ])->columns(2),

                Forms\Components\Section::make('Información de Pago')
                    ->schema([
                        Forms\Components\TextInput::make('stripe_subscription_id')
                            ->label('ID Suscripción Stripe')
                            ->placeholder('sub_...')
                            ->maxLength(255)
                            ->regex('/^sub_[a-zA-Z0-9]+$/')
                            ->helperText('ID único de la suscripción en Stripe (formato: sub_1234567890)')
                            ->nullable()
                            ->validationMessages([
                                'regex' => 'El ID de Stripe debe comenzar con "sub_" seguido de caracteres alfanuméricos',
                            ]),

                        Forms\Components\Select::make('payment_status')
                            ->options([
                                Subscription::PAYMENT_PENDING => 'Pendiente',
                                Subscription::PAYMENT_COMPLETED => 'Completado',
                                Subscription::PAYMENT_FAILED => 'Fallido',
                            ])
                            ->default(Subscription::PAYMENT_PENDING)
                            ->label('Estado del Pago')
                            ->helperText('Estado del último pago'),

                        Forms\Components\TextInput::make('transaction_id')
                            ->label('ID de Transacción')
                            ->maxLength(100)
                            ->placeholder('txn_...')
                            ->helperText('ID de la transacción en el sistema de pagos')
                            ->nullable(),
                    ])->columns(3),

                Forms\Components\Section::make('Información Adicional')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->maxLength(1000)
                            ->helperText('Notas adicionales sobre la suscripción'),

                        Forms\Components\KeyValue::make('features')
                            ->label('Características del Plan')
                            ->keyLabel('Característica')
                            ->valueLabel('Valor')
                            ->columnSpanFull()
                            ->helperText('Características incluidas en el plan'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('user.name')
                    ->searchable()
                    ->sortable()
                    ->label('Usuario'),

                TextColumn::make('plan_type')
                    ->badge()
                    ->colors([
                        'gray' => Subscription::PLAN_BASIC,
                        'warning' => Subscription::PLAN_PREMIUM,
                        'success' => Subscription::PLAN_ENTERPRISE,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Subscription::PLAN_BASIC => 'Básico',
                        Subscription::PLAN_PREMIUM => 'Premium',
                        Subscription::PLAN_ENTERPRISE => 'Enterprise',
                        default => $state,
                    })
                    ->label('Plan'),

                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'success' => Subscription::STATUS_ACTIVE,
                        'danger' => Subscription::STATUS_CANCELLED,
                        'warning' => Subscription::STATUS_EXPIRED,
                        'info' => Subscription::STATUS_PENDING,
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Subscription::STATUS_ACTIVE => 'Activa',
                        Subscription::STATUS_CANCELLED => 'Cancelada',
                        Subscription::STATUS_EXPIRED => 'Expirada',
                        Subscription::STATUS_PENDING => 'Pendiente',
                        default => $state,
                    })
                    ->label('Estado'),

                TextColumn::make('amount')
                    ->money('MXN')
                    ->sortable()
                    ->label('Monto'),

                TextColumn::make('billing_cycle')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Subscription::CYCLE_MONTHLY => 'Mensual',
                        Subscription::CYCLE_QUARTERLY => 'Trimestral',
                        Subscription::CYCLE_YEARLY => 'Anual',
                        default => $state,
                    })
                    ->label('Ciclo'),

                IconColumn::make('auto_renew')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->label('Auto Renovación'),

                TextColumn::make('start_date')
                    ->date()
                    ->sortable()
                    ->label('Inicio'),

                TextColumn::make('end_date')
                    ->date()
                    ->sortable()
                    ->label('Fin'),

                TextColumn::make('next_billing_date')
                    ->date()
                    ->sortable()
                    ->label('Próxima Facturación')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('plan_type')
                    ->label('Tipo de Plan')
                    ->options([
                        Subscription::PLAN_BASIC => 'Básico',
                        Subscription::PLAN_PREMIUM => 'Premium',
                        Subscription::PLAN_ENTERPRISE => 'Enterprise',
                    ]),

                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        Subscription::STATUS_ACTIVE => 'Activa',
                        Subscription::STATUS_CANCELLED => 'Cancelada',
                        Subscription::STATUS_EXPIRED => 'Expirada',
                        Subscription::STATUS_PENDING => 'Pendiente',
                    ]),

                SelectFilter::make('billing_cycle')
                    ->label('Ciclo de Facturación')
                    ->options([
                        Subscription::CYCLE_MONTHLY => 'Mensual',
                        Subscription::CYCLE_QUARTERLY => 'Trimestral',
                        Subscription::CYCLE_YEARLY => 'Anual',
                    ]),

                Filter::make('active_subscriptions')
                    ->label('Solo Suscripciones Activas')
                    ->query(fn (Builder $query): Builder => $query->where('status', Subscription::STATUS_ACTIVE)),

                Filter::make('expiring_soon')
                    ->label('Próximas a Expirar')
                    ->query(fn (Builder $query): Builder => $query->where('status', Subscription::STATUS_ACTIVE)
                        ->where('end_date', '<=', now()->addDays(7))
                        ->where('end_date', '>', now())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
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
            'index' => Pages\ListSubscriptions::route('/'),
            'create' => Pages\CreateSubscription::route('/create'),
            'edit' => Pages\EditSubscription::route('/{record}/edit'),
            'view' => Pages\ViewSubscription::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user']);
    }
} 