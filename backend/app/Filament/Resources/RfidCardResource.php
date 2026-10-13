<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RfidCardResource\Pages;
use App\Models\RfidCard;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class RfidCardResource extends Resource
{
    protected static ?string $model = RfidCard::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Admin';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('admin');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Kartu RFID')->schema([
                Forms\Components\TextInput::make('uid')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50)
                    ->helperText('UID kartu RFID (format hex, contoh: A1B2C3D4)')
                    ->placeholder('A1B2C3D4'),
                Forms\Components\TextInput::make('user_name')
                    ->label('Nama Pemilik')
                    ->required()
                    ->maxLength(100),
                Forms\Components\TextInput::make('balance')
                    ->label('Saldo Awal')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->step(1000)
                    ->minValue(0)
                    ->default(0),
                Forms\Components\Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Forms\Components\Select::make('registered_by')
                    ->label('Didaftarkan Oleh')
                    ->relationship('registeredBy', 'name')
                    ->searchable()
                    ->preload()
                    ->default(auth()->id())
                    ->disabled(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('uid')
                    ->label('UID')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),
                Tables\Columns\TextColumn::make('user_name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('registeredBy.name')
                    ->label('Didaftarkan Oleh')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status'),
                Tables\Filters\SelectFilter::make('registered_by')
                    ->relationship('registeredBy', 'name')
                    ->label('Didaftarkan Oleh'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('topup')
                    ->label('Top Up')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah Top Up')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->step(1000)
                            ->minValue(1000),
                    ])
                    ->action(function (RfidCard $record, array $data) {
                        $record->addBalance($data['amount']);
                    }),
                Action::make('block')
                    ->label('Blokir')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (RfidCard $record) => $record->update(['is_active' => false])),
                Action::make('unblock')
                    ->label('Aktifkan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (RfidCard $record) => ! $record->is_active)
                    ->action(fn (RfidCard $record) => $record->update(['is_active' => true])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRfidCards::route('/'),
            'create' => Pages\CreateRfidCard::route('/create'),
            'edit' => Pages\EditRfidCard::route('/{record}/edit'),
        ];
    }
}
