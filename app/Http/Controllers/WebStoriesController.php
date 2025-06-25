<?php

namespace App\Http\Controllers;

use App\Http\Resources\WebStoriesResource;
use App\Models\Post;
use Illuminate\Http\Request;

class WebStoriesController extends Controller
{
    public function getPosts()
    {
        $posts = Post::with('category')
            ->where('is_active', true)
            ->latest()
            ->paginate(12);

        return WebStoriesResource::collection($posts);
    }
}
