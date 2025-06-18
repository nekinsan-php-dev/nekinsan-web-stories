<?php

use App\Http\Controllers\WebStoriesController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('/api/posts',[WebStoriesController::class, 'getPosts']);
