<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceProfile extends Model
{
    protected $fillable = [
        'user_id',
        'face_id',
        'provider',
        'collection_id',
        'image_path',
        'confidence',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<User, covariant FaceProfile>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
