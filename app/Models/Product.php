<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
    'category',
    'slug',
    'name',
    'image',
    'description',
    'price',
    'features',
    'additional_info',
];


    protected $casts = [
        'features' => 'array', // Allows Laravel to handle JSON conversion
    ];
}
