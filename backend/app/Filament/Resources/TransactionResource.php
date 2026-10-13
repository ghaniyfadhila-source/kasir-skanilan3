<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['device', 'rfidCard']);

        if (! auth()->user()?->hasRole('admin')) {
            $query->where('device_id', auth()->user()->device_id);
        }

        return $query;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Transaksi')->schema([
                Forms\Components\Select::make('device_id')
                    ->label('Device')
                    ->relationship('device', 'name')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\Select::make('rfid_card_id')
                    ->label('Kartu RFID')
                    ->relationship('rfidCard', 'user_name')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'success',
                        'danger' => 'failed',
                        'gray' => 'cancelled',
                    ])
                    ->disabled(),
                Forms\Components\KeyValue::make('items')
                    ->label('Item')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('balance_before')
                    ->label('Saldo Sebelum')
                    ->money('IDR')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('balance_after')
                    ->label('Saldo Sesudah')
                    ->money('IDR')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\DateTimePicker::make('created_at')
                    ->label('Waktu')
                    ->disabled()
                    ->dehydrated(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('device.name')
                    ->label('Device')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('rfidCard.user_name')
                    ->label('Pembeli')
                    ->searchable()
                    ->placeholder('Unknown'),
                Tables\Columns\TextColumn::make('rfidCard.uid')
                    ->label('UID')
                    ->fontFamily('mono')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'success',
                        'danger' => 'failed',
                        'gray' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('balance_before')
                    ->label('Saldo Sebelum')
                    ->money('IDR')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('balance_after')
                    ->label('Saldo Sesudah')
                    ->money('IDR')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('device_id')
                    ->relationship('device', 'name')
                    ->label('Device')
                    ->visible(auth()->user()?->hasRole('admin')),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'], fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'view' => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}
