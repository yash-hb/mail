<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    use HasFactory;

    protected $fillable = ['subject', 'attachments', 'email_date'];

    protected $casts = [
        'attachments' => 'array', // ✅ JSON me store ho raha hai
    ];
}
