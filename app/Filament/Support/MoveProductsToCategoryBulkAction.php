<?php

namespace App\Filament\Support;

use App\Models\Category;
use App\Models\Product;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

class MoveProductsToCategoryBulkAction
{
    public static function make(): BulkAction
    {
        return BulkAction::make('moveToCategory')
            ->label('نقل إلى قسم')
            ->icon(Heroicon::OutlinedArrowRightCircle)
            ->form([
                Select::make('category_id')
                    ->label('القسم الجديد')
                    ->options(fn (): array => CategoryOptions::idNameMap())
                    ->searchable()
                    ->required()
                    ->native(false),
            ])
            ->action(function (Collection $records, array $data): void {
                $categoryId = (int) $data['category_id'];
                $category = Category::query()->find($categoryId);

                if ($category === null) {
                    return;
                }

                $count = Product::query()
                    ->whereIn('id', $records->pluck('id'))
                    ->update(['category_id' => $categoryId]);

                Notification::make()
                    ->success()
                    ->title('تم نقل الأطباق')
                    ->body("نُقل {$count} طبق إلى قسم «{$category->name}».")
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
