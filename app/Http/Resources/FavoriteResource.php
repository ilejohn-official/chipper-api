<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FavoriteResource extends ResourceCollection
{
   public function toArray(Request $request): array
    {
        return [
            'data' => [
                'posts' => $this->resource['posts']->map(fn($post) => [
                    'id' => $post->id,
                    'title' => $post->title,
                    'body' => $post->body,
                    'user' => [
                        'id' => $post->user->id,
                        'name' => $post->user->name,
                    ],
                ]),
                'users' => $this->resource['users']->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->name,
                ]),
            ],
        ];
    }
}
