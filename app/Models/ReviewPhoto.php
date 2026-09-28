<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewPhoto extends Model
{
    protected $fillable = ['review_id', 'path', 'width', 'height', 'position'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}
