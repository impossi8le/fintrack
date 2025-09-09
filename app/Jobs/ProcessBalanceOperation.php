<?php

namespace App\Jobs;

use App\DTO\BalanceOperationDTO;
use App\Services\BalanceService;
use App\Services\UserCacheService;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBalanceOperation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private BalanceOperationDTO $dto) {}

    public function handle(BalanceService $service, UserCacheService $cacheService): void
    {
        // Выполняем операцию с балансом
        $service->operate($this->dto);

        // Получаем пользователя с актуальным балансом
        $user = User::with('balance')->find($this->dto->userId);

        if ($user) {
            // Обновляем кеш пользователя
            $cacheService->put($user);

            // Сбрасываем кеш истории транзакций
            cache()->forget("user:{$this->dto->userId}:transactions");
        }
    }

}
