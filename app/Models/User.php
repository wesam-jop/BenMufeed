<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_admin', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->userRole()->canAccessPanel();
    }

    public function userRole(): UserRole
    {
        if ($this->role instanceof UserRole) {
            return $this->role;
        }

        if (($this->is_admin ?? false) && ! isset($this->attributes['role'])) {
            return UserRole::Admin;
        }

        return UserRole::tryFrom((string) ($this->attributes['role'] ?? '')) ?? UserRole::Cashier;
    }

    public function isAdmin(): bool
    {
        return $this->userRole() === UserRole::Admin;
    }

    public function canAccessPos(): bool
    {
        return $this->userRole()->canAccessPos();
    }

    public function canAccessKitchen(): bool
    {
        return $this->userRole()->canAccessKitchen();
    }

    public function canAccessInvoices(): bool
    {
        return $this->userRole()->canAccessInvoices();
    }

    public function homeUrl(): string
    {
        return match (true) {
            $this->isAdmin() => '/admin',
            $this->canAccessPos() => '/pos',
            $this->canAccessKitchen() => '/pos/printing',
            default => '/',
        };
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            $user->is_admin = $user->userRole() === UserRole::Admin;
        });
    }
}
