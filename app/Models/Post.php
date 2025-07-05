<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Str;

class Post extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = Str::slug($post->title);

                // Ensure unique slug
                $originalSlug = $post->slug;
                $counter = 1;

                while (static::where('slug', $post->slug)->exists()) {
                    $post->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            // Auto-generate meta_title if not provided
            if (empty($post->meta_title)) {
                $post->meta_title = $post->title;
            }
        });

        static::updating(function ($post) {
            if ($post->isDirty('title') && !$post->isDirty('slug')) {
                $post->slug = Str::slug($post->title);

                // Ensure unique slug (excluding current record)
                $originalSlug = $post->slug;
                $counter = 1;

                while (static::where('slug', $post->slug)
                    ->where('id', '!=', $post->id)
                    ->exists()) {
                    $post->slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
            }

            // Auto-update meta_title if it matches the old title and title is changed
            if ($post->isDirty('title') && $post->getOriginal('title') === $post->getOriginal('meta_title')) {
                $post->meta_title = $post->title;
            }
        });

        // Clean up slides when post is deleted
        static::deleting(function ($post) {
            $post->slides()->delete();
        });
    }

    // Scope for active stories
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope for inactive stories
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // Get slides count
    public function getSlidesCountAttribute()
    {
        return $this->slides()->count();
    }

    // Check if story has zoom effects
    public function getHasZoomEffectsAttribute()
    {
        return $this->slides()->where('zoom_effect', true)->exists();
    }

    // Get featured image URL
    public function getFeaturedImageAttribute()
    {
        $media = $this->getFirstMedia('cover');
        return $media ? $media->getUrl('story') : null;
    }

    // Get featured image thumbnail
    public function getFeaturedImageThumbAttribute()
    {
        $media = $this->getFirstMedia('cover');
        return $media ? $media->getUrl('thumb') : null;
    }

    // Check if post has featured image
    public function getHasFeaturedImageAttribute()
    {
        return $this->getFirstMedia('cover') !== null;
    }

    // SEO helper methods
    public function getEffectiveMetaTitleAttribute()
    {
        return $this->meta_title ?: $this->title;
    }

    public function getMetaKeywordsArrayAttribute()
    {
        return $this->meta_keywords ? explode(',', $this->meta_keywords) : [];
    }

    public function getHasCompleteSeoAttribute()
    {
        return !empty($this->meta_title) && !empty($this->meta_description);
    }

    // Media library conversions
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->optimize()
            ->nonQueued();

        $this->addMediaConversion('story')
            ->width(800)
            ->height(600)
            ->sharpen(10)
            ->optimize()
            ->nonQueued();
    }

    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function slides()
    {
        return $this->hasMany(Slide::class)->orderBy('id');
    }
}
