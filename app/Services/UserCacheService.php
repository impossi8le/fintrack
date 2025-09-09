<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserCacheService
{
    // TTL на 1 час
    private int $ttl = 3600;

    public function get(int $userId): ?array
    {
        return Cache::get($this->key($userId));
    }

    public function put(User $user): void
    {
        $data = [
            'id'      => $user->id,
            'name'    => $user->name,
            'email'   => $user->email,
            'balance' => $user->balance?->amount ?? 0,
        ];

        Cache::put($this->key($user->id), $data, $this->ttl);
    }

    public function forget(int $userId): void
    {
        Cache::forget($this->key($userId));
    }

    private function key(int $userId): string
    {
        return "user:{$userId}:data";
    }
}
