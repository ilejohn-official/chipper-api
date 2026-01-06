<?php

namespace App\Http\Resources;

use App\Traits\Fileable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    use Fileable;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'body' => $this->body,
            'image_url' => $this->image_url ? $this->retrieveFile($this->image_url) : null,
            'user' => new UserResource($this->user),
        ];
    }
}
