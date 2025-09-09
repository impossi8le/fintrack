<?php

namespace App\Services;

use App\DTO\BalanceOperationDTO;
use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BalanceService
{
    public function operate(BalanceOperationDTO $dto): Transaction
    {
        return DB::transaction(function () use ($dto) {
            $balance = Balance::lockForUpdate()->firstOrCreate(
                ['user_id' => $dto->userId],
                ['amount' => 0]
            );

            if (!in_array($dto->type, ['credit','debit'], true)) {
                throw new InvalidArgumentException('Invalid type');
            }

            $amount = (string) number_format((float)$dto->amount, 2, '.', '');

            if ($dto->type === 'debit' && (float)$balance->amount < (float)$amount) {
                throw new InvalidArgumentException('Insufficient funds');
            }

            // идемпотентность — если внешний ID приходил, не дублируем
            if ($dto->externalId) {
                $existing = Transaction::where([
                    'user_id' => $dto->userId,
                    'external_id' => $dto->externalId
                ])->first();
                if ($existing) {
                    return $existing;
                }
            }

            if ($dto->type === 'credit') {
                $balance->amount = bcadd((string)$balance->amount, $amount, 2);
            } else {
                $balance->amount = bcsub((string)$balance->amount, $amount, 2);
            }
            $balance->save();

            return Transaction::create([
                'user_id'     => $dto->userId,
                'type'        => $dto->type,
                'amount'      => $amount,
                'description' => $dto->description,
                'external_id' => $dto->externalId,
            ]);
        });
    }
}
