<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;

class AccountTest extends TestCase
{
    public function test_ban(){
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

        // regular user
        $payload = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$D2S6V7Mwgpqa5bqxoY5cPOvsULm3Zj5NW9xNtLnY.a.T4pJryts22', // password
            'user_type' => 'user',
            'is_active' => 1
        ];
        $regularUser = User::create($payload);

        // Act as the newly created user
        Sanctum::actingAs($user, ['admin']);

        // Validation failed - invalid account
        $this->put('/api/admin/account/ban', ['user_id' => null])
            ->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Please choose an account and try again.',
            ]);
        
        // Ban yourself
        $this->put('/api/admin/account/ban', ['user_id' => $user->id])
            ->assertForbidden()
            ->assertJson([
                 'status' => false,
                    'message' => 'You are not allowed to ban your own account.',
            ]);
        
        // Invalid account
        $this->put('/api/admin/account/ban', ['user_id' => 900000000])
            ->assertNotFound()
            ->assertJson([
                'status' => false,
                'message' => 'The user account you selected no longer exist. Please check and try again.',
            ]);

        // Successful ban
        $this->put('/api/admin/account/ban', ['user_id' => $regularUser->id])
            ->assertSuccessful()
            ->assertJson([
                'status' => true
            ]);

    }


    public function test_unban(){
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

        // regular user
        $payload = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$D2S6V7Mwgpqa5bqxoY5cPOvsULm3Zj5NW9xNtLnY.a.T4pJryts22', // password
            'user_type' => 'user',
            'is_active' => 0
        ];
        $regularUser = User::create($payload);

        // Act as the newly created user
        Sanctum::actingAs($user, ['admin']);

        // Validation failed - invalid account
        $this->put('/api/admin/account/unban', ['user_id' => null])
            ->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Please choose an account and try again.',
            ]);
        
        // Ban yourself
        $this->put('/api/admin/account/unban', ['user_id' => $user->id])
            ->assertForbidden()
            ->assertJson([
                 'status' => false,
                    'message' => 'You are not allowed to re-instate your own account.',
            ]);
        
        // Invalid account
        $this->put('/api/admin/account/unban', ['user_id' => 900000000])
            ->assertNotFound()
            ->assertJson([
                'status' => false,
                'message' => 'The user account you selected no longer exist. Please check and try again.',
            ]);

        // Successful ban
        $this->put('/api/admin/account/unban', ['user_id' => $regularUser->id])
            ->assertSuccessful()
            ->assertJson([
                'status' => true
            ]);
    }


    public function test_promote(){
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

        // regular user
        $payload = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$D2S6V7Mwgpqa5bqxoY5cPOvsULm3Zj5NW9xNtLnY.a.T4pJryts22', // password
            'user_type' => 'user',
            'is_active' => 0
        ];
        $regularUser = User::create($payload);

        // Act as the newly created user
        Sanctum::actingAs($user, ['admin']);

        // Validation failed - invalid account
        $this->put('/api/admin/account/promote', ['user_id' => null])
            ->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Please choose an account and try again.',
            ]);
        
        // Ban yourself
        $this->put('/api/admin/account/promote', ['user_id' => $user->id])
            ->assertForbidden()
            ->assertJson([
                'status' => false,
                'message' => 'You are not allowed to promote your own account.',
            ]);
        
        // Invalid account
        $this->put('/api/admin/account/promote', ['user_id' => 900000000])
            ->assertNotFound()
            ->assertJson([
                'status' => false,
                'message' => 'The user account you selected no longer exist or is not a regular account. Please check and try again.',
            ]);

        // Successful promotion
        $this->put('/api/admin/account/promote', ['user_id' => $regularUser->id])
            ->assertSuccessful()
            ->assertJson([
                'status' => true
            ]);
    }


    public function test_demote() {
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

        // second admin user
        $payload = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$D2S6V7Mwgpqa5bqxoY5cPOvsULm3Zj5NW9xNtLnY.a.T4pJryts22', // password
            'user_type' => 'admin',
            'is_active' => 1
        ];
        $regularUser = User::create($payload);

        // Act as the newly created user
        Sanctum::actingAs($user, ['admin']);

        // Validation failed - invalid account
        $this->put('/api/admin/account/demote', ['user_id' => null])
            ->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Please choose an account and try again.',
            ]);
        
        // Ban yourself
        $this->put('/api/admin/account/demote', ['user_id' => $user->id])
            ->assertForbidden()
            ->assertJson([
                'status' => false,
                'message' => 'You are not allowed to demote your own account.',
            ]);
        
        // Invalid account
        $this->put('/api/admin/account/promote', ['user_id' => 900000000])
            ->assertNotFound()
            ->assertJson([
                'status' => false,
                'message' => 'The user account you selected no longer exist or is not a regular account. Please check and try again.',
            ]);

        // Successful promotion
        $this->put('/api/admin/account/demote', ['user_id' => $regularUser->id])
            ->assertSuccessful()
            ->assertJson([
                'status' => true
            ]);
    }
}
