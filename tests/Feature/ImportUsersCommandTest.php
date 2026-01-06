<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ImportUsersCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function fakeUsers(int $count = 3): array
    {
        return collect(range(1, $count))->map(fn ($i) => [
            'id' => $i,
            'name' => "User {$i}",
            'username' => "user{$i}",
            'email' => "user{$i}@example.com",
        ])->toArray();
    }

    public function test_command_imports_correct_number_of_users()
    {
        Http::fake([
            '*' => Http::response($this->fakeUsers(5), 200),
        ]);

        $this->artisan('import:users', [
            'url' => 'https://example.com/users',
            'limit' => 5,
        ])->assertExitCode(0);

        $this->assertDatabaseCount('users', 5);
    }

    public function test_command_respects_limit_parameter()
    {
        Http::fake([
            '*' => Http::response($this->fakeUsers(10), 200),
        ]);

        $this->artisan('import:users', [
            'url' => 'https://example.com/users',
            'limit' => 3,
        ]);

        $this->assertDatabaseCount('users', 3);
    }

    public function test_command_handles_invalid_url()
    {
        $this->artisan('import:users', [
            'url' => 'not-a-valid-url',
            'limit' => 5,
        ])
            ->expectsOutput('Invalid URL provided.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_skips_duplicate_emails()
    {
        User::factory()->create([
            'email' => 'user1@example.com',
        ]);

        Http::fake([
            '*' => Http::response($this->fakeUsers(3), 200),
        ]);

        $this->artisan('import:users', [
            'url' => 'https://example.com/users',
            'limit' => 3,
        ]);

        $this->assertDatabaseCount('users', 3);
    }

    public function test_command_handles_malformed_json()
    {
        Http::fake([
            '*' => Http::response('invalid-json', 200),
        ]);

        $this->artisan('import:users', [
            'url' => 'https://example.com/users',
            'limit' => 5,
        ])
            ->expectsOutput('Malformed JSON response.')
            ->assertExitCode(1);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_command_handles_network_failure()
    {
        Http::fake([
            '*' => fn () => throw new ConnectionException('Network down'),
        ]);

        $this->artisan('import:users', [
            'url' => 'https://example.com/users',
            'limit' => 5,
        ])
            ->expectsOutput('Network error while fetching users.')
            ->assertExitCode(1);
    }
}
