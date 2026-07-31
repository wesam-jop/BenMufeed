<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Materials\MaterialResource;
use App\Models\Material;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryOverview extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCube;

    protected static ?string $navigationLabel = 'المخزون';

    protected static ?string $title = 'المخزون';

    protected static string|\UnitEnum|null $navigationGroup = 'المخزون والفواتير';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'inventory';

    protected string $view = 'filament.pages.inventory-overview';

    public static function canAccess(): bool
    {
        return auth()->user()?->is_admin ?? false;
    }

    public function getTotalStockValueProperty(): float
    {
        return Material::totalActiveStockValue();
    }

    public function getActiveMaterialsCountProperty(): int
    {
        return Material::query()->where('is_active', true)->count();
    }

    public function getLowStockCountProperty(): int
    {
        return Material::lowStockCount();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createMaterial')
                ->label('إضافة مادة')
                ->icon('heroicon-o-plus')
                ->url(MaterialResource::getUrl('create')),
        ];
    }

    public function table(Table $table): Table
    {
        $symbol = config('store.currency_symbol', '$');

        return $table
            ->query(Material::query()->where('is_active', true))
            ->columns([
                TextColumn::make('name')
                    ->label('المادة')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('unit')
                    ->label('الوحدة')
                    ->badge(),
                TextColumn::make('stock_quantity')
                    ->label('الكمية المتوفرة')
                    ->sortable()
                    ->badge()
                    ->color(fn (Material $record) => $record->isLowStock() ? 'danger' : 'success'),
                TextColumn::make('purchase_price')
                    ->label('سعر الشراء')
                    ->suffix(" {$symbol}")
                    ->sortable(),
                TextColumn::make('stock_value')
                    ->label('قيمة المخزون')
                    ->state(fn (Material $record) => $record->stockValue())
                    ->suffix(" {$symbol}")
                    ->sortable(query: fn ($query, string $direction) => $query->orderByRaw("(stock_quantity * purchase_price) {$direction}")),
                TextColumn::make('min_stock')
                    ->label('حد التنبيه')
                    ->placeholder('—'),
            ])
            ->defaultSort('name')
            ->filters([
                Filter::make('low_stock')
                    ->label('مخزون منخفض')
                    ->query(fn (Builder $query) => $query
                        ->whereNotNull('min_stock')
                        ->whereColumn('stock_quantity', '<=', 'min_stock')),
            ])
            ->recordActions([
                EditAction::make()
                    ->url(fn (Material $record): string => MaterialResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make(),
            ])
            ->paginated([10, 25, 50]);
    }
}
