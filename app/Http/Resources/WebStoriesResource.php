<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WebStoriesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'slide' => $this->formatSlidesWithImages(),
            'featured_image' => $this->featured_image,
            'slides_count' => $this->slides_count,
            'has_zoom_effects' => $this->has_zoom_effects,
            'is_active' => (bool) $this->is_active,
            'category' => [
                'id' => $this->category?->id,
                'name' => $this->category?->name,
                'slug' => $this->category?->slug,
            ],
            'seo' => [
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
                'meta_keywords' => $this->meta_keywords,
            ],
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }

    /**
     * Format slides with their images
     */
    private function formatSlidesWithImages(): array
    {
        return $this->slides->map(function ($slide) {
            // Get slide image from media library
            $slideImage = $slide->getFirstMedia('slide_image');

            return [
                'id' => $slide->id,
                'post_id' => $slide->post_id,
                'title' => $slide->title,
                'text_active' => (bool) $slide->text_active,
                'zoom_effect' => (bool) $slide->zoom_effect,
                'text_position' => $slide->text_position,
                'content' => $slide->content,
                'cta_link' => $slide->cta_link,
                'cta_button_show' => (bool) $slide->cta_button_show,
                'slide_image' => $slideImage ? $slideImage->getUrl('story') : null,
                'slide_image_thumb' => $slideImage ? $slideImage->getUrl('thumb') : null,
                'slide_image_original' => $slideImage ? $slideImage->getUrl() : null,
                'created_at' => $slide->created_at->toISOString(),
                'updated_at' => $slide->updated_at->toISOString(),
            ];
        })->toArray();
    }

/**
     * Get additional data that should be wrapped in the resource response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'generated_at' => now()->toISOString(),
            ],
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Illuminate\Http\Response  $response
     * @return void
     */
    public function withResponse(Request $request, $response): void
    {
        // Add SEO headers for better crawling
        $response->header('X-Robots-Tag', 'index, follow');

        if ($this->resource->meta_description) {
            $response->header('X-Meta-Description', $this->resource->meta_description);
        }
    }
}
