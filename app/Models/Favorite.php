<?php

namespace App\Models;

use App\Enums\FavoritableType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Favorite extends Model
{
    use HasFactory;

    protected $fillable = ['post_id', 'user_id', 'favoritable_type', 'favoritable_id'];

    protected $casts = [
        'favoritable_type' => FavoritableType::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function favoritable(): MorphTo
    {
        return $this->morphTo();
    }
}
