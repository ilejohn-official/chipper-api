<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Favorite;
use App\Enums\FavoritableType;
use Illuminate\Database\Eloquent\Factories\Factory;

class FavoriteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Favorite::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => \App\Models\Post::factory(),
            'user_id' => \App\Models\User::factory(),
            'favoritable_id' => fn (array $attributes) => $attributes['post_id'],
            'favoritable_type' => FavoritableType::POST,
        ];
    }

    /**
     * State for favoriting a User.
     */
    public function forUser(User $user = null): static
    {
        return $this->state(fn (array $attributes) => [
            'favoritable_id' => $user ?? User::factory(),
            'favoritable_type' => FavoritableType::USER,
        ]);
    }
}
