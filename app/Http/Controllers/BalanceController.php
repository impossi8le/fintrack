<?php

namespace App\Http\Controllers;

use App\DTO\BalanceOperationDTO;
use App\Http\Requests\BalanceOperateRequest;
use App\Jobs\ProcessBalanceOperation;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Services\UserCacheService;

class BalanceController extends Controller
{
    /**
     * Получить баланс текущего пользователя+кеш
     */
    public function me(Request $request, UserCacheService $cacheService)
    {
        $userId = $request->user()->id;

        if ($cached = $cacheService->get($userId)) {
            return response()->json([
                'user_id' => $cached['id'],
                'amount'  => $cached['balance'],
            ]);
        }

        $user = $request->user()->load('balance');
        $cacheService->put($user);

        return response()->json([
            'user_id' => $user->id,
            'amount'  => $user->balance?->amount ?? 0,
        ]);
    }

    /**
     * Запустить операцию с балансом (асинхронно) + кеш
     */
    public function operate(BalanceOperateRequest $request, UserCacheService $cacheService)
    {
        $userId = $request->user()->id;

        $dto = new BalanceOperationDTO(
            userId: $userId,
            type: $request->input('type'), // string
            amount: $request->input('amount'),
            description: $request->input('description'),
            externalId: $request->input('external_id')
        );

        // забыли кеш
        $cacheService->forget($userId);

        // Асинхронная обработка
        ProcessBalanceOperation::dispatch($dto);

        return response()->json(['status' => 'queued'], 202);
    }

    /**
     * История транзакций пользователя + кеш
     */
    public function history(Request $request)
    {
        $userId = $request->user()->id;
        $cacheKey = "user:{$userId}:transactions";

        // Кешируем историю на 60 секунд
        $items = cache()->remember($cacheKey, 60, function () use ($userId) {
            return Transaction::where('user_id', $userId)
                ->orderByDesc('id')
                ->paginate(20);
        });

        return response()->json($items);
    }

}
