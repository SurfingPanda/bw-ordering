<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomCakeRequest extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'occasion',
        'needed_by',
        'flavor',
        'size',
        'frosting_color',
        'delivery_type',
        'address',
        'fulfillment_branch',
        'description',
        'reference_path',
        'reference_link',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'needed_by' => 'date',
        ];
    }
}
