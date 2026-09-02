<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Hidden(['password', 'remember_token'])]
class Customer extends Authenticatable
{
    use Notifiable;

    protected $guard = 'customer';

    protected $fillable = [
        'phone',
        'name',
        'email',
        'password',
        'whatsapp',
        'loyalty_points',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'loyalty_points' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function favorites(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'customer_favorites')
            ->using(CustomerFavorite::class);
    }

    public function loyaltyTransactions(): HasMany
    {
        return $this->hasMany(LoyaltyTransaction::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function hasPassword(): bool
    {
        return filled($this->getAttributes()['password'] ?? null);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
