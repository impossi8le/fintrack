<?php

namespace App\DTO;

class BalanceOperationDTO
{
    public function __construct(
        public int $userId,
        public string $type,          // credit|debit
        public string $amount,        // decimal string
        public ?string $description = null,
        public ?string $externalId = null
    ) {}
}
