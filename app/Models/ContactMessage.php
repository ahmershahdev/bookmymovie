<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'email', 'subject', 'message', 'user_id', 'is_read', 'is_replied'];
}
