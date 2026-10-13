<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'kasir_id',
        'name',
        'description',
        'price',
        'stock',
        'is_active',
        'category',
        'image_url',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'is_active' => 'boolean',
    ];

    public function kasir()
    {
        return $this->belongsTo(User::class, 'kasir_id');
    }
}