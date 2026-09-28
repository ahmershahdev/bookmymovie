<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GiftCard extends Model
{
    protected $fillable = ['code', 'initial_balance', 'balance', 'recipient_email', 'message', 'expires_at', 'is_active', 'issued_by'];

    protected function casts(): array
    {
        return ['initial_balance' => 'decimal:2', 'balance' => 'decimal:2', 'expires_at' => 'datetime', 'is_active' => 'boolean'];
    }

    public function usable(): bool
    {
        return $this->is_active && (float) $this->balance > 0 && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
