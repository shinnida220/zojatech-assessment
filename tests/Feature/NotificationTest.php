<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Notifications\UserRegistered;
use App\Notifications\InviteUser;
use Notification;

class NotificationTest extends TestCase
{
    public function test_user_registered_notification() {
        Notification::fake(); 
        // user
        $payload = [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => '$2y$10$D2S6V7Mwgpqa5bqxoY5cPOvsULm3Zj5NW9xNtLnY.a.T4pJryts22', // password
            'user_type' => 'admin',
            'is_active' => 1
        ];

        User::create($payload)->saveQuietly();
        $user = User::where('email', $payload['email'])->first();

        $user->notify(new UserRegistered($user));

        Notification::assertSentTo($user, UserRegistered::class, function ($notification, $channels) use ($user) {
            return $notification->toMail($user)->subject === 'Welcome to '. config('app.name');
        });
    }


    public function test_invite_user_notification(){
        // Check that the content contains our search string..
        $notification = new InviteUser('Thank you for honouring our invitation!');
        $rendered = $notification->toMail(fake()->unique()->safeEmail())->render();
 
        // Assert
        $this->assertStringContainsString('Thank you for honouring our invitation!', $rendered);
    }
}
