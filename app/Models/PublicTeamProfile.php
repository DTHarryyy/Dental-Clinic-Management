<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PublicTeamProfile extends Model
{
    protected $fillable = [
        'user_id', 'name', 'title', 'specialties', 'biography',
        'photo_path', 'display_order', 'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
