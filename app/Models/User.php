<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'verification_code',
        'email_verified_at',
        'is_active'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * generateCode - 
     * @param mixed $length
     * @return string with random letters of length $length
     */
    public function generateCode($length = 12)
    {
        $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyz';
        return strtoupper(substr(str_shuffle($permitted_chars), 0, $length));
    }

    public function isVerified(){
        return $this->email_verified_at !== null;
    }

    public function isActive() {
        return abs($this->is_active) === 1;
    }

    /**
     * Get the wallet associated with this user.
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class);
    }
}
