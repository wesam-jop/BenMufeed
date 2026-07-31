<?php

namespace App\Filament\Resources\RestaurantTables\Pages;

use App\Filament\Resources\RestaurantTables\RestaurantTableResource;
use App\Services\RestaurantTableService;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantTable extends EditRecord
{
    protected static string $resource = RestaurantTableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('openQr')
                ->label('رابط QR')
                ->icon('heroicon-o-qr-code')
                ->url(fn () => app(RestaurantTableService::class)->scanUrl($this->record))
                ->openUrlInNewTab(),
            Action::make('qrImage')
                ->label('صورة QR')
                ->icon('heroicon-o-photo')
                ->url(fn () => app(RestaurantTableService::class)->qrImageUrl($this->record))
                ->openUrlInNewTab(),
            Action::make('regenerateQr')
                ->label('رمز جديد')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action(function (): void {
                    app(RestaurantTableService::class)->regenerateToken($this->record);
                    $this->refreshFormData(['qr_token']);
                }),
        ];
    }
}
