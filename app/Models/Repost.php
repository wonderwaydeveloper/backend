<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Repost extends Model
{
    protected $guarded = ['id'];

    protected $fillable = ['user_id', 'post_id', 'quote'];
    
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
