<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationGroup = 'Facturación';

    protected static ?string $navigationLabel = 'Facturas';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Información de Factura')
                    ->schema([
                        Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->required()
                            ->label('Usuario')
                            ->helperText('Usuario al que pertenece la factura'),
                        
                        Select::make('subscription_id')
                            ->relationship('subscription', 'id')
                            ->searchable()
                            ->label('Suscripción')
                            ->nullable()
                            ->helperText('Suscripción asociada (opcional)'),
                        
                        TextInput::make('stripe_invoice_id')
                            ->label('ID Factura Stripe')
                            ->placeholder('in_...')
                            ->nullable()
                            ->maxLength(255)
                            ->regex('/^in_[a-zA-Z0-9]+$/')
                            ->helperText('ID único de la factura en Stripe (formato: in_1234567890)')
                            ->validationMessages([
                                'regex' => 'El ID de Stripe debe comenzar con "in_" seguido de caracteres alfanuméricos',
                            ]),
                        
                        TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->minValue(0)
                            ->step(0.01)
                            ->label('Monto')
                            ->prefix('$')
                            ->placeholder('0.00')
                            ->helperText('Monto de la factura en la moneda especificada')
                            ->validationMessages([
                                'min' => 'El monto debe ser mayor o igual a 0',
                                'numeric' => 'El monto debe ser un número válido',
                            ]),
                        
                        Select::make('currency')
                            ->options([
                                'mxn' => 'MXN - Peso Mexicano',
                                'usd' => 'USD - Dólar Estadounidense',
                            ])
                            ->default('mxn')
                            ->required()
                            ->label('Moneda')
                            ->helperText('Moneda de la factura'),
                        
                        Select::make('status')
                            ->options([
                                'draft' => 'Borrador',
                                'open' => 'Abierta',
                                'paid' => 'Pagada',
                                'void' => 'Anulada',
                                'uncollectible' => 'Incobrable',
                            ])
                            ->required()
                            ->label('Estado')
                            ->helperText('Estado actual de la factura'),
                        
                        DatePicker::make('due_date')
                            ->required()
                            ->label('Fecha de Vencimiento')
                            ->helperText('Fecha límite para el pago'),
                        
                        DatePicker::make('paid_at')
                            ->label('Fecha de Pago')
                            ->nullable()
                            ->helperText('Fecha en que se realizó el pago'),
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
                
                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('mxn')
                    ->sortable(),
                
                TextColumn::make('currency')
                    ->label('Moneda')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'mxn' => 'success',
                        'usd' => 'info',
                        'eur' => 'warning',
                        default => 'gray',
                    }),
                
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->colors([
                        'danger' => 'uncollectible',
                        'warning' => 'open',
                        'success' => 'paid',
                        'secondary' => 'draft',
                        'gray' => 'void',
                    ]),
                
                TextColumn::make('due_date')
                    ->label('Vencimiento')
                    ->date()
                    ->sortable(),
                
                TextColumn::make('paid_at')
                    ->label('Pagado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'open' => 'Abierta',
                        'paid' => 'Pagada',
                        'void' => 'Anulada',
                        'uncollectible' => 'Incobrable',
                    ]),
                
                SelectFilter::make('currency')
                    ->label('Moneda')
                    ->options([
                        'mxn' => 'MXN',
                        'usd' => 'USD',
                        'eur' => 'EUR',
                    ]),
                
                Filter::make('paid_invoices')
                    ->label('Solo Facturas Pagadas')
                    ->query(fn (Builder $query): Builder => $query->where('status', 'paid')),
                
                Filter::make('overdue_invoices')
                    ->label('Facturas Vencidas')
                    ->query(fn (Builder $query): Builder => $query->where('due_date', '<', now())->where('status', '!=', 'paid')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'subscription']);
    }
} 