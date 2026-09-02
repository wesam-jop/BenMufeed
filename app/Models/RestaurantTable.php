<?php

namespace App\Models;

use App\Enums\TableStatus;
use App\Traits\BelongsToBranch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class RestaurantTable extends Model
{
    use BelongsToBranch;

    protected $table = 'restaurant_tables';

    protected $fillable = [
        'branch_id',
        'name',
        'capacity',
        'zone',
        'qr_token',
        'access_code',
        'status',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'status' => TableStatus::class,
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (RestaurantTable $table): void {
            if (blank($table->qr_token)) {
                $table->qr_token = (string) Str::uuid();
            }

            if (blank($table->access_code)) {
                $table->access_code = static::generateAccessCode();
            }
        });
    }

    public static function generateAccessCode(): string
    {
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (static::query()->where('access_code', $code)->exists());

        return $code;
    }

    public function regenerateAccessCode(): void
    {
        $this->update(['access_code' => static::generateAccessCode()]);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(TableSession::class, 'table_id');
    }
}
