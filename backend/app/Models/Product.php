<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
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
            'calories' => 'integer',
            'is_featured' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }
}
