<?php

namespace App\Filament\Resources\RestaurantTables\Tables;

use App\Models\RestaurantTable;
use App\Services\RestaurantTableService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RestaurantTablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label('الرقم')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('label')
                    ->label('الوصف')
                    ->placeholder('—')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->label('مفعّلة')
                    ->boolean(),
                TextColumn::make('qr_token')
                    ->label('رمز QR')
                    ->copyable()
                    ->copyMessage('تم نسخ الرمز')
                    ->limit(16)
                    ->tooltip(fn (RestaurantTable $record) => app(RestaurantTableService::class)->scanUrl($record)),
            ])
            ->headerActions([
                Action::make('venueQr')
                    ->label('الباركود الموحّد للطاولات')
                    ->icon('heroicon-o-qr-code')
                    ->color('primary')
                    ->tooltip('باركود واحد لكل الطاولات — يمسحه الزبون ثم يختار رقم طاولته')
                    ->url(fn (): string => route('tables.qr.venue'))
                    ->openUrlInNewTab(),
                Action::make('printAllQr')
                    ->label('تصدير باركود كل طاولة (ZIP)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (): string => route('tables.qr.all'))
                    ->openUrlInNewTab(),
            ])
            ->recordActions([
                EditAction::make(),
                Action::make('printQr')
                    ->label('تصدير QR (صورة)')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (RestaurantTable $record): string => route('tables.qr.png', $record))
                    ->openUrlInNewTab(),
                Action::make('regenerateQr')
                    ->label('رمز جديد')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->modalHeading('إنشاء رمز QR جديد؟')
                    ->modalDescription('الرمز القديم على الطاولة لن يعمل بعد التحديث.')
                    ->action(function (RestaurantTable $record): void {
                        app(RestaurantTableService::class)->regenerateToken($record);

                        Notification::make()
                            ->title('تم إنشاء رمز جديد')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('number');
    }
}
