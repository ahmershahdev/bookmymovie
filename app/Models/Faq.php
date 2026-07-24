<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $table = 'faqs';

    protected $fillable = ['category', 'question', 'answer', 'sort_order', 'is_active', 'created_by'];
}
