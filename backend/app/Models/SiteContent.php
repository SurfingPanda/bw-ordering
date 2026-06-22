<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteContent extends Model
{
    protected $table = 'site_content';

    public $incrementing = false;

    protected $fillable = ['id', 'data'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
        ];
    }
}
