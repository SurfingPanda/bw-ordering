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
        'customer_phone',
        'items',
        'voucher',
        'payment_method',
        'payment_status',
        'payment_ref',
        'delivery_type',
        'delivery_speed',
        'pickup_store_id',
        'pickup_branch',
        'address',
        'notes',
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
