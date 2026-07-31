<?php

namespace App\Filament\Resources\HomeSliders\Pages;

use App\Filament\Resources\HomeSliders\HomeSliderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListHomeSliders extends ListRecords
{
    protected static string $resource = HomeSliderResource::class;

    public function getSubheading(): ?string
    {
        return 'شريحة جديدة تُضاف في آخر الترتيب. لإعادة الترتيب: اسحب الصفوف من أيقونة السحب.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
