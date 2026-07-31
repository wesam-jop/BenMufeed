<?php

namespace App\Filament\Support;

use App\Models\Category;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class DeleteCategoryActions
{
    public static function makeDeleteAction(): DeleteAction
    {
        return DeleteAction::make()
            ->label('حذف القسم')
            ->modalHeading('حذف القسم')
            ->modalDescription(fn (Category $record): string => self::modalDescription($record))
            ->form(fn (Category $record): array => self::confirmationForm($record))
            ->action(function (Category $record, array $data): void {
                self::deleteCategory($record, (bool) ($data['delete_products'] ?? false));
            });
    }

    public static function makeDeleteBulkAction(): DeleteBulkAction
    {
        return DeleteBulkAction::make()
            ->label('حذف الأقسام المحددة')
            ->modalHeading('حذف الأقسام المحددة')
            ->modalDescription(fn (Collection $records): string => self::bulkModalDescription($records))
            ->form(fn (Collection $records): array => self::bulkConfirmationForm($records))
            ->action(function (Collection $records, array $data): void {
                $deleteProducts = (bool) ($data['delete_products'] ?? false);
                $categories = $records->filter(fn (Model $record): bool => $record instanceof Category);

                $blocked = $categories->filter(
                    fn (Category $category): bool => $category->products()->count() > 0,
                );

                if ($blocked->isNotEmpty() && ! $deleteProducts) {
                    $totalProducts = $blocked->sum(fn (Category $category): int => $category->products()->count());

                    Notification::make()
                        ->danger()
                        ->title('لا يمكن حذف بعض الأقسام')
                        ->body("{$blocked->count()} قسم يحتوي على {$totalProducts} طبق. انقل الأطباق أو فعّل خيار الحذف مع الأطباق.")
                        ->send();

                    return;
                }

                foreach ($categories as $category) {
                    self::deleteCategory($category, $deleteProducts, notify: false);
                }

                Notification::make()
                    ->success()
                    ->title('تم حذف الأقسام المحددة')
                    ->send();
            });
    }

    public static function deleteCategory(Category $category, bool $deleteProducts, bool $notify = true): void
    {
        $productsCount = $category->products()->count();

        if ($productsCount > 0 && ! $deleteProducts) {
            if ($notify) {
                Notification::make()
                    ->danger()
                    ->title('لا يمكن حذف القسم')
                    ->body("القسم «{$category->name}» يحتوي على {$productsCount} طبق. انقل الأطباق إلى قسم آخر، أو فعّل خيار حذف الأطباق مع القسم.")
                    ->send();
            }

            return;
        }

        if ($productsCount > 0) {
            $category->products()->delete();
        }

        $category->delete();

        if ($notify) {
            Notification::make()
                ->success()
                ->title('تم حذف القسم')
                ->body($productsCount > 0
                    ? "تم حذف القسم «{$category->name}» مع {$productsCount} طبق."
                    : "تم حذف القسم «{$category->name}».")
                ->send();
        }
    }

    /**
     * @return array<int, Checkbox|Placeholder>
     */
    protected static function confirmationForm(Category $record): array
    {
        $count = $record->products()->count();

        if ($count === 0) {
            return [];
        }

        return [
            Placeholder::make('warning')
                ->label('')
                ->content(new HtmlString(
                    "<p class=\"text-sm text-gray-600 dark:text-gray-400\">هذا القسم يحتوي على <strong>{$count}</strong> طبق. بدون تفعيل الخيار أدناه لن يُحذف القسم.</p>",
                )),
            Checkbox::make('delete_products')
                ->label('حذف القسم وجميع أطباقه نهائياً')
                ->accepted()
                ->validationMessages([
                    'accepted' => 'يجب تأكيد حذف الأطباق مع القسم للمتابعة.',
                ]),
        ];
    }

    protected static function modalDescription(Category $record): string
    {
        $count = $record->products()->count();

        if ($count === 0) {
            return 'هل أنت متأكد من حذف هذا القسم؟ لا توجد أطباق مرتبطة به.';
        }

        return 'هذا القسم يحتوي على أطباق. يمكنك إلغاء الحذف، أو تأكيد حذف القسم مع جميع أطباقه.';
    }

    /**
     * @param  Collection<int, Model>  $records
     */
    protected static function bulkConfirmationForm(Collection $records): array
    {
        $categories = $records->filter(fn (Model $record): bool => $record instanceof Category);
        $withProducts = $categories->filter(fn (Category $category): bool => $category->products()->count() > 0);
        $totalProducts = $withProducts->sum(fn (Category $category): int => $category->products()->count());

        if ($withProducts->isEmpty()) {
            return [];
        }

        return [
            Placeholder::make('warning')
                ->label('')
                ->content(new HtmlString(
                    '<p class="text-sm text-gray-600 dark:text-gray-400">'
                    .$withProducts->count().' قسم من أصل '.$categories->count()
                    .' يحتوي على أطباق (مجموع <strong>'.$totalProducts.'</strong> طبق).'
                    .' بدون تفعيل الخيار أدناه لن تُحذف هذه الأقسام.</p>',
                )),
            Checkbox::make('delete_products')
                ->label('حذف الأقسام المحددة وجميع أطباقها نهائياً')
                ->accepted()
                ->validationMessages([
                    'accepted' => 'يجب تأكيد حذف الأطباق مع الأقسام للمتابعة.',
                ]),
        ];
    }

    /**
     * @param  Collection<int, Model>  $records
     */
    protected static function bulkModalDescription(Collection $records): string
    {
        $categories = $records->filter(fn (Model $record): bool => $record instanceof Category);
        $withProducts = $categories->filter(fn (Category $category): bool => $category->products()->count() > 0);

        if ($withProducts->isEmpty()) {
            return 'هل أنت متأكد من حذف الأقسام المحددة؟ لا توجد أطباق مرتبطة بها.';
        }

        return 'بعض الأقسام المحددة تحتوي على أطباق. يمكنك الإلغاء، أو تأكيد الحذف مع جميع الأطباق.';
    }
}
