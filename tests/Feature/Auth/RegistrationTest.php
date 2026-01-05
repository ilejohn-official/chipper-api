<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_can_register(): void
    {
        $response = $this->json('POST', route('register'), [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated();
        $response->assertJsonStructure(['data' => [
            'name', 'email',
        ], 'token']);
    }
}
