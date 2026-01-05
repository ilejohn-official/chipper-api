<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;

class ImportUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:users 
                            {url : Public JSON URL} 
                            {limit : Maximum number of users to import}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from a given URL';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $url = (string) $this->argument('url');
        $limit = (int) $this->argument('limit');

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            $this->error('Invalid URL provided.');
            return Command::FAILURE;
        }

        if ($limit <= 0) {
            $this->error('Limit must be greater than zero.');
            return Command::FAILURE;
        }

        $this->info("Fetching users from {$url} (limit: {$limit})");

        try {
        $response = Http::timeout(10)
            ->retry(3, 200)
            ->get($url);
        } catch (ConnectionException $e) {
            $this->error('Network error while fetching users.');
            return Command::FAILURE;
        }

        if ($response->failed()) {
            $this->error('Failed to fetch user data.');
            return Command::FAILURE;
        }

        $users = collect($response->json());

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Malformed JSON response.');
            return Command::FAILURE;
        }

        if (! $users->every(fn ($user) => is_array($user))) {
            $this->error('Invalid JSON structure received.');
            return Command::FAILURE;
        }

        $imported = $this->importUsers($users, $limit);

        $this->info("Import completed successfully. Users imported: {$imported}");

        return Command::SUCCESS;
    }

    /**
     * Import users into the database.
     */
    protected function importUsers(Collection $users, int $limit): int
    {
        return $users
            ->take($limit)
            ->filter(fn ($user) => isset($user['name'], $user['email']) && filter_var($user['email'], FILTER_VALIDATE_EMAIL))
            ->each(function ($user) {
                User::updateOrCreate(
                    ['email' => $user['email']],
                    ['name' => $user['name'], 'password' => Hash::make('password')]
                );
            })
            ->count();
    }
}
