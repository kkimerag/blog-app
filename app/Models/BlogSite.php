<?php

namespace App\Models;

use Canvas\Models\Post;
use Canvas\Models\Tag;
use Canvas\Models\Topic;
use Canvas\Models\User as CanvasUser;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogSite extends Model
{
    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'domain',
        'path',
        'is_default',
        'settings',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'settings' => 'array',
    ];

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'site_id');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class, 'site_id');
    }

    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class, 'site_id');
    }

    public function canvasUsers(): HasMany
    {
        return $this->hasMany(CanvasUser::class, 'site_id');
    }
}
