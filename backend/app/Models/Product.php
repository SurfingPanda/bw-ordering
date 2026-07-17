<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'product_id',
        'name',
        'type',
        'bundle_product_ids',
        'description',
        'price',
        'original_price',
        'image_path',
        'features',
        'calories',
        'is_featured',
        'status',
        'category',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'float',
            'original_price' => 'float',
            'features' => 'array',
            'bundle_product_ids' => 'array',
            'calories' => 'integer',
            'is_featured' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }
}
