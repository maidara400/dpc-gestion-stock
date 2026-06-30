<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookConfig extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'url', 'active', 'events', 'secret'];

    protected $casts = [
        'active' => 'boolean',
        'events' => 'array',
    ];
}
