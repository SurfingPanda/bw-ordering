<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    protected $fillable = ['code', 'type', 'value', 'label', 'active'];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'active' => 'boolean',
        ];
    }
}
