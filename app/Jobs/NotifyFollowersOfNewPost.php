<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\User;
use Illuminate\Bus\Queueable;
use App\Enums\FavoritableType;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewPostFromFavoriteUser;

class NotifyFollowersOfNewPost implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private Post $post)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $followers = User::whereHas('favorites', function ($query) {
            $query->where('favoritable_type', FavoritableType::USER)
                  ->where('favoritable_id', $this->post->user_id);
        })->get();

        Notification::send($followers, new NewPostFromFavoriteUser($this->post));
    }
}
