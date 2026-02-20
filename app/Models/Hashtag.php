<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

class Hashtag extends Model
{
    use HasFactory, Searchable;

    protected $fillable = ['name', 'slug', 'posts_count'];

    protected $casts = ['posts_count' => 'integer'];

    public function posts()
    {
        return $this->belongsToMany(Post::class)->withTimestamps();
    }

    public static function createFromText($text)
    {
        preg_match_all('/#(\w+)/u', $text, $matches);

        $hashtags = [];
        foreach ($matches[1] as $tag) {
            $hashtags[] = self::firstOrCreate([
                'slug' => Str::slug($tag),
            ], [
                'name' => $tag,
            ]);
        }

        return $hashtags;
    }
}
