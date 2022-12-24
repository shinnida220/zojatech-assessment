<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use App\Models\Wallet;

class WalletTest extends TestCase
{
    public function test_withdrawal() {
        // regular user
        $payload = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$D2S6V7Mwgpqa5bqxoY5cPOvsULm3Zj5NW9xNtLnY.a.T4pJryts22', // password
            'user_type' => 'user',
            'is_active' => 1
        ];
        $user = User::create($payload);
        $user->wallet->delete();
        $user->createToken('apiToken', ['user']);
        $user->save();

        // Act as the newly created user
        Sanctum::actingAs($user, ['user']);

        // Invalid request
        $this->post('/api/wallet/withdraw')
            ->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Data validation failed'
            ]);

        // NO wallet
        $this->post('/api/wallet/withdraw', ['amount' => 1000])
            ->assertForbidden()
            ->assertJson([
                'status' => false, 
                'message' => 'Withdrawal service is unavilable at the moment. Please try again later.'
            ]);
        
        
        $wallet = Wallet::create([
            'user_id' => $user->id, 'balance' => 10000, 'history' => []
        ]);

        // Inactive user
        $user->is_active = 0;
        $user->save();

        $this->post('/api/wallet/withdraw', ['amount' => 1000])
            ->assertForbidden()
            ->assertJson([
                'status' => false, 
                'message' => 'Withdrawal failed. Your account is suspended.'
            ]);

        $user->is_active = 1;
        $user->save();
    
        // Insufficient Balance
        $this->post('/api/wallet/withdraw', ['amount' => 100000])
            ->assertForbidden()
            ->assertJson([
                'status' => false, 
                'message' => 'Withdrawal failed. Your have insufficient balance.'
            ]);

        // Valid withdrawal
        $this->post('/api/wallet/withdraw', ['amount' => 1000])
            ->assertOk()
            ->assertJson([
                'status' => true
            ]);
    }


    public function test_funding() {
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

        // Validation failed
        $this->post('/api/admin/wallet/fund', ['amount' => 100000, 'user_id' => null])
            ->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Data validation failed',
            ]);

        // Wrong or non user wallet
        $this->post('/api/admin/wallet/fund', ['amount' => 100000, 'user_id' => $user->id])
            ->assertForbidden()
            ->assertJson([
                'status' => false, 
                'message' => 'Wallet funding service is unavilable at the moment. Please try again later.'
            ]);

        // Wrong or non user wallet
        $this->post('/api/wallet/fund', ['amount' => 1000, 'user_id' => $user->id])
            ->assertForbidden()
            ->assertJson([
                'message' => 'Invalid ability provided.'
            ]);

        // Wrong or non user wallet
        $this->post('/api/wallet/fund-hack', ['amount' => 1000, 'user_id' => $user->id])
            ->assertForbidden()
            ->assertJson([
                'status' => false
            ]);

        // Valid funding
        $this->post('/api/admin/wallet/fund', ['amount' => 10000, 'user_id' => $regularUser->id])
            ->assertOk()
            ->assertJson([
                'status' => true,
            ]);
    }
}
