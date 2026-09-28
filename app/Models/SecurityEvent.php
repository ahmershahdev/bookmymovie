<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityEvent extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['type', 'ip_address', 'user_id', 'method', 'path', 'user_agent', 'meta'];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'created_at' => 'datetime',
        ];
    }
}
