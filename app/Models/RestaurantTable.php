<?php

namespace App\Models;

use App\Services\RestaurantTableService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RestaurantTable extends Model
{
    protected $fillable = [
        'number',
        'qr_token',
        'label',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public static function generateToken(): string
    {
        return Str::lower(Str::random(48));
    }

    public function displayName(): string
    {
        if (filled($this->label)) {
            return $this->label;
        }

        return 'طاولة '.$this->number;
    }

    protected static function booted(): void
    {
        static::saved(fn () => RestaurantTableService::forgetCache());
        static::deleted(fn () => RestaurantTableService::forgetCache());
    }
}
