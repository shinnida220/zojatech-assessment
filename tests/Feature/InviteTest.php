<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;

class InviteTest extends TestCase
{

    public function test_invite() {
        // admin user
        $payload = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$D2S6V7Mwgpqa5bqxoY5cPOvsULm3Zj5NW9xNtLnY.a.T4pJryts22', // password
            'user_type' => 'admin',
            'is_active' => 1
        ];
        $user = User::create($payload);
        $user->createToken('apiToken', ['admin']);
        $user->save();

        // Act as the newly created user
        Sanctum::actingAs($user, ['admin']);

        // Validation failed - invalid account
        $this->post('/api/admin/invite', ['user_id' => null])
            ->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Please ensure you enter a valid email and invite text.',
            ]);

        // Validation failed - invalid account
        $this->post('/api/admin/invite-multiple', ['email' => null, 'invite_text' => 'some text here'])
            ->assertStatus(500)
            ->assertJson([
                'status' => false,
                'message' => 'An unexpected error has occured. Please try again later',
            ]);

        // Validation failed - invalid account
        $this->post('/api/admin/invite-multiple', ['email' => ['fakemail@domain', 'invalid-email'], 'invite_text' => 'some text here'])
            ->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Please ensure you enter valid emails and a proper invite text.',
            ]);

        // valid invites
        $this->post('/api/admin/invite', ['email' => fake()->unique()->safeEmail(), 'invite_text' => 'some text here'])
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);

        // valid invites
        $this->post('/api/admin/invite-multiple', ['email' => [fake()->unique()->safeEmail(), fake()->unique()->safeEmail()], 'invite_text' => 'some text here'])
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);
    }
}
