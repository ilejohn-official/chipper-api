<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Arr;
use App\Enums\FavoritableType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use App\Jobs\NotifyFollowersOfNewPost;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewPostFromFavoriteUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_not_create_a_post()
    {
        $response = $this->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $response->assertStatus(401);
    }

    public function test_a_user_can_create_a_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'id', 'title', 'body', 'image_url'
                ]
            ])
            ->assertJson([
                'data' => [
                    'title' => 'Test Post',
                    'body' => 'This is a test post.',
                    'image_url' => null,
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
            'image_url' => null,
        ]);
    }

    public function test_a_user_can_update_a_post()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'Original title',
            'body' => 'Original body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($user)->putJson(route('posts.update', ['post' => $id]), [
            'title' => 'Updated title',
            'body' => 'Updated body.',
        ]);

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'title' => 'Updated title',
                    'body' => 'Updated body.',
                ]
            ]);

        $this->assertDatabaseHas('posts', [
            'title' => 'Updated title',
            'body' => 'Updated body.',
            'id' => $id,
            'image_url' => null
        ]);
    }

    public function test_a_user_can_not_update_a_post_by_other_user()
    {
        $john = User::factory()->create(['name' => 'John']);
        $jack = User::factory()->create(['name' => 'Jack']);

        $response = $this->actingAs($john)->postJson(route('posts.store'), [
            'title' => 'Original title',
            'body' => 'Original body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($jack)->putJson(route('posts.update', ['post' => $id]), [
            'title' => 'Updated title',
            'body' => 'Updated body.',
        ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('posts', [
            'title' => 'Original title',
            'body' => 'Original body.',
            'id' => $id,
        ]);
    }

    public function test_a_user_can_destroy_one_of_his_posts()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('posts.store'), [
            'title' => 'My title',
            'body' => 'My body.',
        ]);

        $id = Arr::get($response->json(), 'data.id');

        $response = $this->actingAs($user)->deleteJson(route('posts.destroy', ['post' => $id]));

        $response->assertNoContent();

        $this->assertDatabaseMissing('posts', [
            'id' => $id,
        ]);
    }

    public function test_notification_is_queued_with_correct_post_data_when_post_is_created()
    {
        Queue::fake();

        $author = User::factory()->create();

        $response = $this->actingAs($author)->postJson(route('posts.store'), [
            'title' => 'Test Post',
            'body' => 'This is a test post.',
        ]);

        $postId = $response->json('data.id');

        Queue::assertPushed(function (NotifyFollowersOfNewPost $job) use ($postId) {
            return $job->post->id === $postId;
        });
    }

    public function test_job_sends_notifications_to_followers()
    {
        Notification::fake();

        $author = User::factory()->create();
        $follower = User::factory()->create();

        $follower->favorites()->create([
            'favoritable_type' => FavoritableType::USER,
            'favoritable_id' => $author->id,
        ]);

        $post = Post::factory()->create(['user_id' => $author->id]);

        (new NotifyFollowersOfNewPost($post))->handle();

        Notification::assertSentTo($follower, NewPostFromFavoriteUser::class);
    }

    public function test_a_user_can_create_a_post_with_valid_image()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $image = UploadedFile::fake()->image('post.jpg', 800, 600);

        $response = $this->actingAs($user)
            ->postJson(route('posts.store'), [
                'title' => 'Post with Image',
                'body' => 'This post has an image',
                'image' => $image,
            ])
            ->assertCreated();

        Storage::disk('public')->assertExists("images/posts/{$user->id}/" . $image->hashName());

        $this->assertDatabaseHas('posts', [
            'title' => 'Post with Image',
            'user_id' => $user->id,
        ]);

        $response->assertJsonStructure([
            'data' => ['id', 'title', 'body', 'image_url']
        ]);
    }

    public function test_invalid_image_file_types_are_rejected()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $invalidFile = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $this->actingAs($user)
            ->postJson(route('posts.store'), [
                'title' => 'Post with Invalid File',
                'body' => 'This should fail',
                'image' => $invalidFile,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);

        Storage::disk('public')->assertMissing("images/posts/{$user->id}/" . $invalidFile->hashName());
    }

    public function test_post_response_includes_image_url()
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $image = UploadedFile::fake()->image('test.png', 100, 100);

        $response = $this->actingAs($user)
            ->postJson(route('posts.store'), [
                'title' => 'Test Post',
                'body' => 'Test body',
                'image' => $image,
            ])
            ->assertCreated();

        $imageUrl = $response->json('data.image_url');

        $this->assertNotNull($imageUrl);
        $this->assertStringContainsString("images/posts/{$user->id}/", $imageUrl);
        $this->assertStringEndsWith($image->hashName(), $imageUrl);
    }

}
