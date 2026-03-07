<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'price',
        'image',
        'description',
        'category_id',
        'subcategory_id',
        'location',
        'tag',
    ];

    /**
     * Get the category that owns the product.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the subcategory that owns the product.
     */
    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }
    public $timestamps = false;
}

