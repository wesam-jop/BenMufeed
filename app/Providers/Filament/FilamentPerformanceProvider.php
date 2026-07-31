<?php

namespace App\Providers\Filament;

use Filament\Tables\Table;
use Illuminate\Support\ServiceProvider;

class FilamentPerformanceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Table::configureUsing(function (Table $table): void {
            $table
                ->deferLoading()
                ->defaultPaginationPageOption(25)
                ->paginationPageOptions([10, 25, 50, 100])
                ->striped();
        });
    }
}
