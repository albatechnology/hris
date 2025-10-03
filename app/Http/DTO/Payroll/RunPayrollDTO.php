<?php

namespace App\Http\DTO\Payroll;

class RunPayrollDTO {
    public function __construct(
        public ?string $branch_id,
        public string $company_id,
        public string $period,
        public string $payment_schedule,
        public ?string $user_ids,
        public array $array_user_ids = [],
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            branch_id: $data['branch_id'] ?? null,
            company_id: $data['company_id'],
            period: $data['period'],
            payment_schedule: $data['payment_schedule'],
            user_ids: $data['user_ids'] ?? null,
            array_user_ids: !empty($data['user_ids']) ? explode(',', $data['user_ids']) : [],
        );
    }
}
