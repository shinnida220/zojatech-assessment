<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;
use App\Models\User;

class AuthTest extends TestCase
{
    // use RefreshDatabase;
    
    function randomString($n) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
    
        for ($i = 0; $i < $n; $i++) {
            $index = rand(0, strlen($characters) - 1);
            $randomString .= $characters[$index];
        }
        return $randomString;
    }

    /** test signup */
    public function test_signup(){
        $payload = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password_confirmation' => '$2y$10$D2S6V7Mwgpqa5bqxoY5cPOvsULm3Zj5NW9xNtLnY.a.T4pJryts22',
            'password' => '$2y$10$D2S6V7Mwgpqa5bqxoY5cPOvsULm3Zj5NW9xNtLnY.a.T4pJryts22', // password
            'user_type' => 'user',
        ];

        // validation failed
        $this->post("api/signup", ['email' => $payload['email']])
            ->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Data validation failed',
            ]);

        // 500 error failed
        $this->post("api/signup", ['email' => $payload['email'], 'password' => '---------'])
            ->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Data validation failed',
            ]);

        $this->post("api/signup", [...$payload, 'email_verified_at' => null])
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Registration successful. A verification link has been sent to your email address.',
            ]);

        
    }

    /** test otp */
    public function test_verify_email(){
        $payload = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            // 'email_verified_at' => now(),
            'password' => '$2y$10$D2S6V7Mwgpqa5bqxoY5cPOvsULm3Zj5NW9xNtLnY.a.T4pJryts22', // password
            'user_type' => 'user',
            'verification_code' => (new User)->generateCode(6)
        ];
        
        $user = User::create($payload);

        // Successful
        $this->post('/api/email/verify', ['email_verification_code' => $user->verification_code])
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Your email address was successfully verified. Please proceed to login.',
            ]);

        // Already verified
        $payload = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$D2S6V7Mwgpqa5bqxoY5cPOvsULm3Zj5NW9xNtLnY.a.T4pJryts22', // password
            'user_type' => 'user',
            'verification_code' => (new User)->generateCode(6)
        ];
        
        $user = User::create($payload);
        $this->post('/api/email/verify', ['email_verification_code' => $user->verification_code])
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Your email is already verifed.'
            ]);
        
        // Wrong code
        $this->post('/api/email/verify', ['email_verification_code' => '000ABC'])
            ->assertNotFound();
        
        // Malformed
        $this->post('/api/email/verify', ['email_verification_code' => '000'])
            ->assertStatus(400);
    }

    /** test signin */
    public function test_signin(){
        $payload = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => '$2y$10$D2S6V7Mwgpqa5bqxoY5cPOvsULm3Zj5NW9xNtLnY.a.T4pJryts22', // password
            'user_type' => 'user',
            'is_active' => 0
        ];
        $user = User::create($payload);
        
        // Validation error
        $response = $this->post('/api/login', ['email' => $payload['email']]);
        $response->assertStatus(400)
            ->assertJson([
                'status' => false,
                'message' => 'Please ensure your email and password are entered and try again',
            ]);
        
        /** Unverified */
        $response = $this->post('/api/login', ['email' => $user->email, 'password' => '$$Pass123$']);
        $response->assertUnauthorized()
            ->assertJson([
                'status' => false,
                'message' => 'Login failed. Please verify your email to proceed.'
            ]);

        $user->email_verified_at = now();
        $user->save();

        /** in_active */
        $response = $this->post('/api/login', ['email' => $user->email, 'password' => '$$Pass123$']);
        $response->assertUnauthorized()
            ->assertJson([
                'status' => false,
                'message' => 'Login failed. Your account has been suspended.'
            ]);


        $user->user_type = 'admin';
        $user->is_active = 1;
        $user->save();

        $response = $this->post('/api/admin/login', ['email' => $user->email, 'password' => '$$Pass123$']);
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Login successful',
            ]);

        $user->user_type = 'user';
        $user->save();

        $response = $this->post('/api/login', ['email' => $user->email, 'password' => '$$Pass123$']);
        $response->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Login successful',
            ]);
    }

    /** test failed login */
    public function test_failed_signin(){
        $payload = [
            'email' => fake()->unique()->safeEmail(),
            'password' => $this->randomString(8),
            'user_type' => 'user',
        ];
        
        $this->post('/api/login', ['email' => $payload['email'], 'password' => 'pass123'])
            ->assertUnauthorized()
            ->assertJson([
                'status' => false,
                'message' => 'Login failed. Incorrect email/password combination',
            ]);
        
        // inpersonate an admin
        $this->post('/api/admin/login', ['email' => $payload['email'], 'password' => $payload['password']])
            ->assertUnauthorized()
            ->assertJson([
                'status' => false,
                'message' => 'Login failed. Incorrect email/password combination',
            ]);
    }

    /** Test profile */
    public function test_profile(){
        $payload = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            // 'password_confirmation' => '$2y$10$D2S6V7Mwgpqa5bqxoY5cPOvsULm3Zj5NW9xNtLnY.a.T4pJryts22',
            'password' => '$2y$10$D2S6V7Mwgpqa5bqxoY5cPOvsULm3Zj5NW9xNtLnY.a.T4pJryts22', // password
            'user_type' => 'user',
        ];
        $user = User::create($payload);

        // Act as the newly created user
        Sanctum::actingAs($user, ['user']);

        $this->get('/api/profile')
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Profile information retrieved successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id
                    ]
                ]
            ]);
    }

    /** test signout */
    public function test_signout(){
        $payload = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$D2S6V7Mwgpqa5bqxoY5cPOvsULm3Zj5NW9xNtLnY.a.T4pJryts22', // password
            'user_type' => 'user',
        ];
        $user = User::create($payload);

        // Act as the newly created user
        Sanctum::actingAs($user, ['user']);

        $response = $this->post('/api/logout')
            ->assertStatus(200)
            ->assertJson([
                'status' => true,
                'message' => 'Logout successful'
            ]);
    }
}
