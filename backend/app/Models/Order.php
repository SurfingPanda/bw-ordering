<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'customer_name',
        'customer_email',
        'items',
        'voucher',
        'subtotal',
        'discount',
        'delivery',
        'vat',
        'total',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'items' => 'array',
            'subtotal' => 'float',
            'discount' => 'float',
            'delivery' => 'float',
            'vat' => 'float',
            'total' => 'float',
        ];
    }
}
