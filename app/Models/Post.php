<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

// Model dai dien cho bang `posts` trong database.
class Post extends Model
{
    // Ho tro tao du lieu gia (factory) khi test/seed.
    use HasFactory;

    // Bang posts chi dung created_at, khong co updated_at.
    public const UPDATED_AT = null;

    // Danh sach cot duoc phep gan hang loat qua create()/update().
    protected $fillable = [
        'id',
        'user_id',
        'image',
        'caption',
        'status',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        $imagePath = $this->attributes['image'] ?? null;

        if (empty($imagePath)) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $imagePath)) {
            return $imagePath;
        }

        return Storage::url($imagePath);
    }

    // Moi bai viet thuoc ve 1 user (posts.user_id -> users.id).
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Moi bai viet co nhieu anh (post_images.post_id -> posts.id).
    public function images(): HasMany
    {
        return $this->hasMany(PostImage::class);
    }
}
