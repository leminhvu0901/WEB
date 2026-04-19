<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// Model dai dien cho bang `post_images`.
class PostImage extends Model
{
    // Ho tro factory khi test/seed du lieu.
    use HasFactory;

    // Bang post_images khong su dung cot updated_at.
    public const UPDATED_AT = null;

    // Cac cot duoc phep mass assignment.
    protected $fillable = [
        'post_id',
        'image_url',
        'is_thumbnail',
    ];

    // Tu dong ep kieu gia tri thumbnail ve boolean.
    protected $casts = [
        'is_thumbnail' => 'boolean',
    ];

    // Moi anh thuoc ve 1 bai viet (post_images.post_id -> posts.id).
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }
}
