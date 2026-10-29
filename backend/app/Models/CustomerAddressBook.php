<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerAddressBook extends Model
{
    protected $primaryKey = 'user_id';
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['user_id', 'addresses', 'imported_at'];
    protected function casts(): array
    {
        return ['addresses' => 'array', 'imported_at' => 'datetime'];
    }
}
