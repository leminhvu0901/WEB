<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'caption',
        'like_count',
        'comment_count',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PostImage::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(PostComment::class)->latest('id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(PostShare::class);
    }
}
