<?php

namespace App\Http\Resources\Loan;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $loanDetails = $this->details->sortBy([
            ['payment_period_year', 'asc'],
            ['payment_period_month', 'asc'],
        ]);

        // Hitung remaining loan secara backward
        $remaining = $this->amount;

        $loanDetailsWithRemaining = $loanDetails->map(function ($detail) use (&$remaining) {
            $remaining -= $detail->basic_payment;
            $detail->remaining_loan = max($remaining, 0);
            return $detail;
        });

        return [
            ...parent::toArray($request),
            'details' => $loanDetailsWithRemaining,
        ];
    }
}
