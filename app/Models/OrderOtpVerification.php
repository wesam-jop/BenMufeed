<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderOtpVerification extends Model
{
    protected $fillable = [
        'phone',
        'name',
        'code_hash',
        'attempts',
        'expires_at',
        'verified_at',
        'session_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }
}
