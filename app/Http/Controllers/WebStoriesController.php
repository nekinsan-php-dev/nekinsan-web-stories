<?php

namespace App\Http\Controllers;

use App\Http\Resources\WebStoriesResource;
use App\Models\Post;
use Illuminate\Http\Request;

class WebStoriesController extends Controller
{
    public function getPosts()
    {
        $posts = Post::where('is_active', 1)->latest()->paginate(10);

        return WebStoriesResource::collection($posts);
    }
}
