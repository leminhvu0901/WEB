<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    public const UPDATED_AT = null;

    protected $fillable = [
        'username',
        'email',
        'password',
        'avatar',
        'role',
        'phone',
        'address',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    protected $appends = [
        'avatar_url',
    ];

    public function getAvatarUrlAttribute(): ?string
    {
        $avatarPath = $this->attributes['avatar'] ?? null;

        if (empty($avatarPath)) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $avatarPath)) {
            return $avatarPath;
        }

        return Storage::url($avatarPath);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
