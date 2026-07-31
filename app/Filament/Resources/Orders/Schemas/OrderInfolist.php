<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class OrderInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')->label('رقم الطلب'),
                TextEntry::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(function ($state): string {
                        $enum = $state instanceof OrderStatus ? $state : OrderStatus::from((string) $state);

                        return $enum->label();
                    }),
                TextEntry::make('source')
                    ->label('المصدر')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ($state instanceof \App\Enums\OrderSource ? $state : \App\Enums\OrderSource::from((string) $state))->label()),
                TextEntry::make('cashier.name')
                    ->label('الكاشير')
                    ->placeholder('—')
                    ->visible(fn ($record) => $record->source === \App\Enums\OrderSource::Pos),
                TextEntry::make('service_type')
                    ->label('نوع الخدمة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? ($state instanceof \App\Enums\PosServiceType ? $state : \App\Enums\PosServiceType::from((string) $state))->label() : '—')
                    ->placeholder('—'),
                TextEntry::make('table_number')
                    ->label('رقم الطاولة')
                    ->placeholder('—')
                    ->visible(fn ($record) => $record->service_type === \App\Enums\PosServiceType::Table),
                TextEntry::make('restaurantTable.label')
                    ->label('طاولة (QR)')
                    ->placeholder('—')
                    ->visible(fn ($record) => $record->restaurant_table_id !== null),
                TextEntry::make('customer_name')->label('اسم العميل')->placeholder('—'),
                TextEntry::make('customer_phone')->label('هاتف العميل')->placeholder('—'),
                TextEntry::make('delivery_location_note')
                    ->label('وصف موقع التوصيل')
                    ->placeholder('—')
                    ->visible(fn ($record) => filled($record->delivery_location_note)),
                TextEntry::make('delivery_map')
                    ->label('الموقع على الخريطة')
                    ->state(fn ($record) => $record->deliveryMapUrl())
                    ->url(fn ($record) => $record->deliveryMapUrl())
                    ->openUrlInNewTab()
                    ->placeholder('—')
                    ->visible(fn ($record) => $record->deliveryMapUrl() !== null),
                TextEntry::make('paid_amount')
                    ->label('المبلغ المقبوض')
                    ->suffix(' $')
                    ->placeholder('—')
                    ->visible(fn ($record) => $record->source === \App\Enums\OrderSource::Pos),
                TextEntry::make('closed_at')
                    ->label('تاريخ الإغلاق')
                    ->dateTime()
                    ->placeholder('—')
                    ->visible(fn ($record) => $record->closed_at !== null),
                TextEntry::make('delivery_fee')
                    ->label('رسوم التوصيل')
                    ->suffix(' $')
                    ->placeholder('—')
                    ->visible(fn ($record) => (float) ($record->delivery_fee ?? 0) > 0),
                TextEntry::make('total')->label('الإجمالي')->suffix(' $'),
                TextEntry::make('reviewed_at')->label('تاريخ القبول')->dateTime()->placeholder('—'),
                TextEntry::make('cancelled_at')->label('تاريخ الإلغاء')->dateTime()->placeholder('—'),
                TextEntry::make('created_at')->label('تاريخ الإنشاء')->dateTime(),
                TextEntry::make('items_summary')
                    ->label('الأطباق')
                    ->columnSpanFull(),
                TextEntry::make('customer_note')
                    ->label('ملاحظة العميل')
                    ->placeholder('—')
                    ->columnSpanFull(),
            ]);
    }
}
