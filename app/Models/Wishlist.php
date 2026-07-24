<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Wishlist extends Model
{
    public $timestamps = false;

    protected $fillable = ['user_id', 'movie_id', 'created_at'];

    public function movie(): BelongsTo
    {
        return $this->belongsTo(Movie::class);
    }
}
