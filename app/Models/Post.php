<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'content',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'is_active',
    ];

    protected $casts = [
        'content' => 'array',
        'meta_keywords' => 'array', // Add this line to cast meta_keywords as array
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'content' => '{}',
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
        });
    }

    // Accessor for slides (from content JSON with images)
    protected function slides(): Attribute
    {
        return Attribute::make(
            get: function () {
                $slides = $this->content['slides'] ?? [];
                $slideImages = $this->getMedia('slides');

                // Attach slide images to their respective slides
                foreach ($slides as $index => $slide) {
                    if (isset($slideImages[$index])) {
                        $slides[$index]['slide_image'] = $slideImages[$index]->getUrl('story');
                        $slides[$index]['slide_image_thumb'] = $slideImages[$index]->getUrl('thumb');
                    } else {
                        $slides[$index]['slide_image'] = null;
                        $slides[$index]['slide_image_thumb'] = null;
                    }
                }

                return $slides;
            }
        );
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
        return count($this->slides);
    }

    // Check if story has zoom effects
    public function getHasZoomEffectsAttribute()
    {
        $slides = $this->slides;
        foreach ($slides as $slide) {
            if (isset($slide['zoom_effect']) && $slide['zoom_effect']) {
                return true;
            }
        }
        return false;
    }

    // Get featured image URL
    public function getFeaturedImageAttribute()
    {
        $media = $this->getFirstMedia('cover');
        return $media ? $media->getUrl('story') : null;
    }

    // Get content with embedded slide images
    public function getContentWithImagesAttribute()
    {
        $content = $this->content;
        if (isset($content['slides'])) {
            $content['slides'] = $this->slides; // This will use the accessor that includes images
        }
        return $content;
    }

    // Media library conversions
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections('slides', 'cover')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->optimize()
            ->nonQueued();

        $this->addMediaConversion('story')
            ->performOnCollections('slides', 'cover')
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
}
