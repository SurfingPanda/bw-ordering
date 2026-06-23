<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = ['code', 'type', 'value', 'label', 'active', 'expires_at'];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'active' => 'boolean',
            'expires_at' => 'date:Y-m-d',
        ];
    }
}
