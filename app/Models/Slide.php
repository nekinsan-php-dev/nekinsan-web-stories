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
        'cta_link',
        'cta_button_show',
    ];

    protected $casts = [
        'text_active' => 'boolean',
        'zoom_effect' => 'boolean',
        'cta_button_show' => 'boolean',
    ];

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

    // Accessors
    public function getSlideImageAttribute()
    {
        $media = $this->getFirstMedia('slide_image');
        return $media ? $media->getUrl('story') : null;
    }

    public function getSlideImageThumbAttribute()
    {
        $media = $this->getFirstMedia('slide_image');
        return $media ? $media->getUrl('thumb') : null;
    }

    public function getSlideImageOriginalAttribute()
    {
        $media = $this->getFirstMedia('slide_image');
        return $media ? $media->getUrl() : null;
    }

    public function getHasSlideImageAttribute()
    {
        return $this->getFirstMedia('slide_image') !== null;
    }

    // CTA helper methods
    public function getHasCtaButtonAttribute()
    {
        return $this->cta_button_show && !empty($this->cta_link);
    }

    public function getIsValidCtaLinkAttribute()
    {
        return filter_var($this->cta_link, FILTER_VALIDATE_URL) !== false;
    }

    public function getCtaButtonTextAttribute()
    {
        // You can customize this based on your needs
        return 'Call to Action';
    }

    // Scopes
    public function scopeWithCta($query)
    {
        return $query->where('cta_button_show', true)->whereNotNull('cta_link');
    }

    public function scopeWithoutCta($query)
    {
        return $query->where('cta_button_show', false)->orWhereNull('cta_link');
    }

    public function scopeWithZoomEffect($query)
    {
        return $query->where('zoom_effect', true);
    }

    public function scopeActive($query)
    {
        return $query->where('text_active', true);
    }

    // Relationships
    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
