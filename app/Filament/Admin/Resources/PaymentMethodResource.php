<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PaymentMethodResource\Pages;
use App\Models\PaymentMethod;
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
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Facturación';

    protected static ?string $navigationLabel = 'Métodos de Pago';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información del Método de Pago')
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required()
                            ->label('Usuario')
                            ->helperText('Usuario propietario del método de pago'),
                        
                        TextInput::make('stripe_payment_method_id')
                            ->label('ID Método de Pago Stripe')
                            ->placeholder('pm_...')
                            ->required()
                            ->maxLength(255)
                            ->regex('/^pm_[a-zA-Z0-9]+$/')
                            ->helperText('ID único del método de pago en Stripe (formato: pm_1234567890)')
                            ->validationMessages([
                                'regex' => 'El ID de Stripe debe comenzar con "pm_" seguido de caracteres alfanuméricos',
                            ]),
                        
                        Select::make('type')
                            ->options([
                                'card' => 'Tarjeta',
                                'bank_account' => 'Cuenta Bancaria',
                                'sepa_debit' => 'Débito SEPA',
                                'sofort' => 'Sofort',
                            ])
                            ->required()
                            ->label('Tipo')
                            ->helperText('Tipo de método de pago'),
                        
                        TextInput::make('last4')
                            ->label('Últimos 4 dígitos')
                            ->maxLength(4)
                            ->minLength(4)
                            ->numeric()
                            ->rules(['digits:4'])
                            ->placeholder('1234')
                            ->helperText('Ingrese los últimos 4 dígitos de la tarjeta')
                            ->nullable()
                            ->validationMessages([
                                'digits' => 'Debe ingresar exactamente 4 dígitos',
                                'numeric' => 'Solo se permiten números',
                            ]),
                        
                        Select::make('brand')
                            ->options([
                                'visa' => 'Visa',
                                'mastercard' => 'Mastercard',
                                'amex' => 'American Express',
                                'discover' => 'Discover',
                                'jcb' => 'JCB',
                                'diners_club' => 'Diners Club',
                                'unionpay' => 'UnionPay',
                            ])
                            ->label('Marca')
                            ->nullable()
                            ->helperText('Marca de la tarjeta (solo para tarjetas)'),
                        
                        Toggle::make('is_default')
                            ->label('Método por Defecto')
                            ->default(false)
                            ->helperText('Marcar como método de pago predeterminado'),
                    ])->columns(2),
                
                Section::make('Metadatos')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->label('Metadatos')
                            ->nullable()
                            ->columnSpanFull()
                            ->helperText('Información adicional en formato clave-valor'),
                    ])->collapsible(),
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
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('stripe_payment_method_id')
                    ->label('ID Stripe')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->colors([
                        'primary' => 'card',
                        'success' => 'bank_account',
                        'warning' => 'sepa_debit',
                        'info' => 'sofort',
                    ]),
                
                TextColumn::make('last4')
                    ->label('Últimos 4')
                    ->formatStateUsing(fn ($state) => $state ? "**** {$state}" : '-'),
                
                TextColumn::make('brand')
                    ->label('Marca')
                    ->badge()
                    ->colors([
                        'primary' => 'visa',
                        'success' => 'mastercard',
                        'warning' => 'amex',
                        'info' => 'discover',
                        'danger' => 'jcb',
                        'secondary' => 'diners_club',
                        'gray' => 'unionpay',
                    ]),
                
                IconColumn::make('is_default')
                    ->label('Por Defecto')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('gray'),
                
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'card' => 'Tarjeta',
                        'bank_account' => 'Cuenta Bancaria',
                        'sepa_debit' => 'Débito SEPA',
                        'sofort' => 'Sofort',
                    ]),
                
                SelectFilter::make('brand')
                    ->label('Marca')
                    ->options([
                        'visa' => 'Visa',
                        'mastercard' => 'Mastercard',
                        'amex' => 'American Express',
                        'discover' => 'Discover',
                        'jcb' => 'JCB',
                        'diners_club' => 'Diners Club',
                        'unionpay' => 'UnionPay',
                    ]),
                
                Filter::make('default_methods')
                    ->label('Solo Métodos por Defecto')
                    ->query(fn (Builder $query): Builder => $query->where('is_default', true)),
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
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
            'view' => Pages\ViewPaymentMethod::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user']);
    }
} 