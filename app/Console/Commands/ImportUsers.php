<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

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

        if ($limit <= 0) {
            $this->error('Limit must be greater than zero.');
            return Command::FAILURE;
        }

        $this->info("Fetching users from {$url} (limit: {$limit})");

        $response = Http::timeout(10)
            ->retry(3, 200)
            ->get($url);

        if ($response->failed()) {
            $this->error('Failed to fetch user data.');
            return Command::FAILURE;
        }

        $users = collect($response->json());

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
            ->filter(fn ($user) => isset($user['name'], $user['email']))
            ->each(function ($user) {
                User::updateOrCreate(
                    ['email' => $user['email']],
                    ['name' => $user['name']]
                );
            })
            ->count();
    }
}
