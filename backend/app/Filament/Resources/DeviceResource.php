<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeviceResource\Pages;
use App\Models\Device;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'Device Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Device Info')->schema([
                Forms\Components\TextInput::make('device_id')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(100)
                    ->helperText('Unique ID dari ESP8266 (MAC address)'),
                Forms\Components\TextInput::make('name')
                    ->maxLength(100),
                Forms\Components\Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'maintenance' => 'Maintenance',
                    ])
                    ->default('active')
                    ->required(),
                Forms\Components\Select::make('kasir_id')
                    ->label('Kasir')
                    ->options(User::role('kasir')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->name} ({$record->email})")
                    ->placeholder('Pilih kasir (optional)'),
            ]),
            Forms\Components\Section::make('Token & Status')->schema([
                Forms\Components\TextInput::make('api_token')
                    ->label('API Token')
                    ->disabled()
                    ->dehydrated(false)
                    ->copyable()
                    ->copyMessage('Token disalin!')
                    ->helperText('Token untuk autentikasi device. Dihasilkan otomatis saat create.'),
                Forms\Components\TextInput::make('firmware_version')
                    ->label('Firmware Version')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\TextInput::make('ip_address')
                    ->label('IP Address')
                    ->disabled()
                    ->dehydrated(false),
                Forms\Components\DateTimePicker::make('last_seen_at')
                    ->label('Last Seen')
                    ->disabled()
                    ->dehydrated(false),
            ]),
        ]);
    }

    protected static function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['api_token'])) {
            $device = new Device;
            $data['api_token'] = $device->generateApiToken();
        }

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('device_id')
                    ->label('Device ID')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'maintenance',
                        'danger' => 'inactive',
                    ]),
                Tables\Columns\TextColumn::make('kasir.name')
                    ->label('Kasir')
                    ->placeholder('Belum diassign'),
                Tables\Columns\TextColumn::make('firmware_version')
                    ->label('FW Version')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_online')
                    ->label('Online')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->isOnline()),
                Tables\Columns\TextColumn::make('last_seen_at')
                    ->label('Last Seen')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Never')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'maintenance' => 'Maintenance',
                    ]),
                Tables\Filters\Filter::make('online')
                    ->query(fn ($query) => $query->where('last_seen_at', '>', now()->subSeconds(30)))
                    ->label('Online'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('regenerate_token')
                    ->label('Reset Token')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(fn (Device $record) => $record->update(['api_token' => $record->generateApiToken()])),
                Action::make('view_transactions')
                    ->label('Transaksi')
                    ->icon('heroicon-o-document-text')
                    ->url(fn (Device $record) => TransactionResource::getUrl('index', ['device' => $record->device_id])),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListDevices::route('/'),
            'create' => Pages\CreateDevice::route('/create'),
            'edit' => Pages\EditDevice::route('/{record}/edit'),
        ];
    }
}
