<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Post extends Model implements HasMedia
{
    use HasFactory,InteractsWithMedia;

    protected $fillable = [
        'title',
        'content',
        'is_active',
    ];

    protected $casts = [
        'content' => 'array',
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'content' => '{}',
    ];

    // Accessor for title (from content JSON)
    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->attributes['title'] ?? 'Untitled Story',
        );
    }

    // Accessor for slides (from content JSON)
    protected function slides(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->content['slides'] ?? [],
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

    // Get first slide image for preview
    public function getPreviewImageAttribute()
    {
        $slides = $this->slides;
        foreach ($slides as $slide) {
            if (isset($slide['image']) && $slide['image_active']) {
                return $slide['image'];
            }
        }
        return null;
    }

    // Get story excerpt from first slide
    public function getExcerptAttribute($limit = 150)
    {
        $slides = $this->slides;
        foreach ($slides as $slide) {
            if (isset($slide['content']) && $slide['text_active']) {
                return \Str::limit(strip_tags($slide['content']), $limit);
            }
        }
        return 'No content available.';
    }
}
