<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PostImage extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'post_id',
        'image_url',
        'is_thumbnail',
    ];

    protected $casts = [
        'is_thumbnail' => 'boolean',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
