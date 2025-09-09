<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = ['name','email','password'];
    protected $hidden = ['password','remember_token'];

    public function balance() { return $this->hasOne(Balance::class); }
    public function transactions() { return $this->hasMany(Transaction::class); }

    protected static function booted()
    {
        static::deleting(function (User $user) {
            app(\App\Services\UserCacheService::class)->forget($user->id);
        });
    }

}
