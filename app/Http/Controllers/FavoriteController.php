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
        $favorites = $request->user()->favorites;
        return FavoriteResource::collection($favorites);
    }

    public function store(CreateFavoriteRequest $request, Post $post)
    {
        $request->user()->favorites()->create(['post_id' => $post->id]);

        return response()->noContent(Response::HTTP_CREATED);
    }

    public function destroy(Request $request, Post $post)
    {
        $favorite = $request->user()->favorites()->where('post_id', $post->id)->firstOrFail();

        $favorite->delete();

        return response()->noContent();
    }

    public function storeUser(FavoriteUserRequest $request, User $user)
    {
        $request->user()->favorites()->create([
            'favoritable_type' => FavoritableType::USER,
            'favoritable_id' => $user->id,
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

