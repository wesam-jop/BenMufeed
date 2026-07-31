<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Widgets\ProductSalesOverviewWidget;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    public function getSubheading(): ?string
    {
        return 'إدارة القائمة — غيّر القسم من الجدول مباشرة أو انقل عدة أطباق دفعة واحدة. '.config('store.currency_notice');
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProductSalesOverviewWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
