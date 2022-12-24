<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Wallet;
use App\Jobs\UserRegistrationJob;

class UserObserver
{

    /**
     * Handle events after all transactions are committed.
     * @var bool
     */
    public $afterCommit = true;


    /**
     * Handle the User "created" event.
     *
     * @param  \App\Models\User  $user
     * @return void
     */
    public function created(User $user)
    {
        // Setup a virtual wallet for the new user
        Wallet::create([
            'user_id' => $user->id,
            'balance' => 0,
            'history' => []
        ]);

        UserRegistrationJob::dispatch($user);
    }
}
