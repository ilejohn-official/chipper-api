<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Enums\FavoritableType;
use App\Http\Resources\FavoriteResource;
use App\Http\Requests\FavoriteUserRequest;
use App\Http\Requests\CreateFavoriteRequest;

/**
 * @group Favorites
 *
 * API endpoints for managing favorites
 */
class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = $request->user()->favorites()->get();

        $postIds = $favorites->where('favoritable_type', FavoritableType::POST->value)
            ->pluck('favoritable_id');
        $userIds = $favorites->where('favoritable_type', FavoritableType::USER->value)
            ->pluck('favoritable_id');

        $posts = Post::with('user')->whereIn('id', $postIds)->get();
        $users = User::whereIn('id', $userIds)->get();

        return new FavoriteResource(compact('posts', 'users'));
    }

    public function store(CreateFavoriteRequest $request, Post $post)
    {
        $request->user()->favorites()->create([
            'favoritable_type' => FavoritableType::POST->value,
            'favoritable_id' => $post->id,
            'post_id' => $post->id
        ]);

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, Post $post)
    {
        $favorite = $request->user()->favorites()
            ->where('post_id', $post->id)
            ->where('favoritable_type', FavoritableType::POST->value)
            ->where('favoritable_id', $post->id)
            ->firstOrFail();

        $favorite->delete();

        return response()->noContent();
    }

    public function storeUser(FavoriteUserRequest $request, User $user)
    {
        $request->user()->favorites()->create([
            'favoritable_type' => FavoritableType::USER,
            'favoritable_id' => $user->id,
            'post_id' => 0
        ]);

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroyUser(Request $request, User $user)
    {
        $favorite = $request->user()->favorites()
            ->where('favoritable_type', FavoritableType::USER)
            ->where('favoritable_id', $user->id)
            ->firstOrFail();

        $favorite->delete();

        return response()->noContent();
    }
}
