<?php

namespace App\Filament\Resources\Categories\RelationManagers;

use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $relatedResource = ProductResource::class;

    protected static ?string $title = 'أطباق القسم';

    protected static ?string $modelLabel = 'طبق';

    protected static ?string $pluralModelLabel = 'أطباق';

    public function form(Schema $schema): Schema
    {
        return ProductResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return ProductsTable::configure($table)
            ->headerActions([
                CreateAction::make()
                    ->label('إضافة طبق')
                    ->fillForm(fn (): array => [
                        'category_id' => $this->getOwnerRecord()->getKey(),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['category_id'] = $this->getOwnerRecord()->getKey();

                        return $data;
                    }),
            ])
            ->emptyStateHeading('لا توجد أطباق في هذا القسم')
            ->emptyStateDescription('أضف طبقاً جديداً أو انقل أطباقاً من قسم آخر عبر عمود «القسم».');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        if (! $ownerRecord instanceof Category) {
            return null;
        }

        $count = $ownerRecord->products()->count();

        return $count > 0 ? (string) $count : null;
    }
}
