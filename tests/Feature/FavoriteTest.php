<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Post;
use App\Models\User;
use App\Enums\FavoritableType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_not_favorite_a_post()
    {
        $post = Post::factory()->create();

        $this->postJson(route('favorites.store', ['post' => $post]))
            ->assertStatus(401);
    }

    public function test_a_user_can_favorite_a_post()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_type' => FavoritableType::POST->value,
            'favoritable_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_remove_a_post_from_his_favorites()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_type' => FavoritableType::POST->value,
            'favoritable_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'favoritable_type' => FavoritableType::POST->value,
            'favoritable_id' => $post->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_not_remove_a_non_favorited_item()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroy', ['post' => $post]))
            ->assertNotFound();
    }

    public function test_a_guest_can_not_favorite_a_user()
    {
        $user = User::factory()->create();

        $this->postJson(route('favorites.storeUser', ['user' => $user]))
            ->assertStatus(401);
    }

    public function test_a_user_can_favorite_another_user()
    {
        $user = User::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $userToFavorite]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_type' => FavoritableType::USER->value,
            'favoritable_id' => $userToFavorite->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_can_remove_a_user_from_favorites()
    {
        $user = User::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $userToFavorite]))
            ->assertCreated();

        $this->assertDatabaseHas('favorites', [
            'favoritable_type' => FavoritableType::USER->value,
            'favoritable_id' => $userToFavorite->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->deleteJson(route('favorites.destroyUser', ['user' => $userToFavorite]))
            ->assertNoContent();

        $this->assertDatabaseMissing('favorites', [
            'favoritable_type' => FavoritableType::USER->value,
            'favoritable_id' => $userToFavorite->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_cannot_favorite_themselves()
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $user]))
            ->assertForbidden();
    }

    public function test_a_user_can_favorite_posts_and_users_simultaneously()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $userToFavorite]))
            ->assertCreated();

        $this->assertEquals(2, $user->favorites()->count());

        $this->assertDatabaseHas('favorites', [
            'favoritable_type' => FavoritableType::POST->value,
            'favoritable_id' => $post->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('favorites', [
            'favoritable_type' => FavoritableType::USER->value,
            'favoritable_id' => $userToFavorite->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_favorites_index_returns_correct_json_structure()
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $userToFavorite]))
            ->assertCreated();

        $response = $this->actingAs($user)
            ->getJson(route('favorites.index'))
            ->assertOk();

        $response->assertJsonStructure([
            'data' => [
                'posts' => [
                    '*' => ['id', 'title', 'body', 'user' => ['id', 'name']]
                ],
                'users' => [
                    '*' => ['id', 'name']
                ]
            ]
        ]);
    }

    public function test_favorites_index_posts_include_user_data()
    {
        $user = User::factory()->create();
        $postAuthor = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $postAuthor->id]);

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post]))
            ->assertCreated();

        $response = $this->actingAs($user)
            ->getJson(route('favorites.index'))
            ->assertOk();

        $response->assertJsonPath('data.posts.0.id', $post->id)
            ->assertJsonPath('data.posts.0.title', $post->title)
            ->assertJsonPath('data.posts.0.body', $post->body)
            ->assertJsonPath('data.posts.0.user.id', $postAuthor->id)
            ->assertJsonPath('data.posts.0.user.name', $postAuthor->name);
    }

    public function test_favorites_index_users_are_listed_separately()
    {
        $user = User::factory()->create();
        $userToFavorite = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $userToFavorite]))
            ->assertCreated();

        $response = $this->actingAs($user)
            ->getJson(route('favorites.index'))
            ->assertOk();

        $response->assertJsonPath('data.users.0.id', $userToFavorite->id)
            ->assertJsonPath('data.users.0.name', $userToFavorite->name)
            ->assertJsonCount(0, 'data.posts');
    }

    public function test_favorites_index_empty_favorites_returns_empty_arrays()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('favorites.index'))
            ->assertOk();

        $response->assertJsonPath('data.posts', [])
            ->assertJsonPath('data.users', []);
    }

    public function test_favorites_index_mixed_favorites_are_properly_grouped()
    {
        $user = User::factory()->create();
        $post1 = Post::factory()->create();
        $post2 = Post::factory()->create();
        $userToFavorite1 = User::factory()->create();
        $userToFavorite2 = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post1]))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('favorites.store', ['post' => $post2]))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $userToFavorite1]))
            ->assertCreated();

        $this->actingAs($user)
            ->postJson(route('favorites.storeUser', ['user' => $userToFavorite2]))
            ->assertCreated();

        $response = $this->actingAs($user)
            ->getJson(route('favorites.index'))
            ->assertOk();

        $response->assertJsonCount(2, 'data.posts')
            ->assertJsonCount(2, 'data.users');
    }
}
