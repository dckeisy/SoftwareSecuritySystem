<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['code','name','description','quantity','price'];

    protected function casts(): array {
        return [
            'quantity' => 'integer',
            'price' => 'float',
        ];
    }
}
