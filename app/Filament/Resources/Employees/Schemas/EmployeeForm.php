<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        $symbol = config('store.currency_symbol', '$');

        return $schema
            ->components([
                TextInput::make('name')
                    ->label('اسم العامل')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2),
                TextInput::make('phone')
                    ->label('رقم الهاتف')
                    ->tel()
                    ->maxLength(32),
                TextInput::make('monthly_salary')
                    ->label('الراتب الشهري')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix($symbol)
                    ->default(0)
                    ->helperText('يُسجَّل كاستحقاق (دائن) عند تسجيل الرواتب الشهرية.'),
                Toggle::make('is_active')
                    ->label('نشط')
                    ->default(true),
                Textarea::make('notes')
                    ->label('ملاحظات')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }
}
