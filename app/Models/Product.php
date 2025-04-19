<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Product
 * 
 * Modelo para representar productos en el sistema
 * 
 * @package App\Models
 */
class Product extends Model
{
    use HasFactory;

    /**
     * Atributos que pueden ser asignados masivamente
     * 
     * @var array
     */
    protected $fillable = ['code','name','description','quantity','price','user_id'];

    /**
     * Atributos que deben ser convertidos a tipos nativos
     * 
     * @var array
     */
    protected $casts = [
        'quantity' => 'integer',
        'price' => 'float',
    ];
    
    /**
     * Relación con el modelo de Usuario que creó el producto
     * 
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
