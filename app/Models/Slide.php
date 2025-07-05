<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Slide extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'post_id',
        'title',
        'text_active',
        'zoom_effect',
        'text_position',
        'content',
    ];

    protected $casts = [
        'text_active' => 'boolean',
        'zoom_effect' => 'boolean',
    ];

    protected $attributes = [
        'text_active' => false,
        'zoom_effect' => false,
        'text_position' => 'center',
    ];

    // Get slide image URL
    public function getSlideImageAttribute()
    {
        $media = $this->getFirstMedia('slide_image');
        return $media ? $media->getUrl('story') : null;
    }

    // Get slide thumbnail URL
    public function getSlideImageThumbAttribute()
    {
        $media = $this->getFirstMedia('slide_image');
        return $media ? $media->getUrl('thumb') : null;
    }

    // Check if slide has image
    public function getHasImageAttribute()
    {
        return $this->getFirstMedia('slide_image') !== null;
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
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
