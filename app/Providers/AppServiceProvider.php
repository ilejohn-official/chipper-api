<?php

namespace App\Providers;

use App\Models\Post;
use App\Models\User;
use App\Enums\FavoritableType;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Relations\Relation;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::morphMap([
            FavoritableType::POST->value => Post::class,
            FavoritableType::USER->value => User::class,
        ]);
    }
}
