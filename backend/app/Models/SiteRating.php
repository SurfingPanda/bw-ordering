<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteRating extends Model
{
    public const PROMPT_DEFAULTS = [
        'enabled' => true,
        'delaySeconds' => 5,
    ];

    protected $fillable = ['user_id', 'rating', 'page'];
}
