<?php

namespace App\Data\Transfers;

use App\Data\BaseData;

class InitiateTransferData extends BaseData
{
    public function __construct(
        public string $sender_account_id,
        public string $receiver_account_number,
        public float $amount,
        public ?string $description = null,
    ) {}
}
