<?php

namespace App\Imports\UpdatePayrollComponent;

use App\Models\PayrollComponent;
use App\Models\User;
use Illuminate\Support\Collection;

class ImportDetails
{
    public array $datas = [];

    public function __construct(private int $companyId) {}

    public function collection(Collection $rows)
    {
        unset($rows[0]);
        $userNiks = $rows->pluck(0)->unique();
        $payrollComponentNames = $rows->pluck(2)->unique();
        $users = User::where('company_id', $this->companyId)->whereIn('nik', $userNiks)->get(['id', 'name', 'nik'])->each->setAppends([]);
        $payrollComponents = PayrollComponent::active()->where('company_id', $this->companyId)->whereIn('name', $payrollComponentNames)->get(['id', 'name', 'type']);

        $grouped = [];

        foreach ($rows as $row) {
            $user = $users->where('nik', $row[0])->first();
            $payrollComponent = $payrollComponents->where('name', $row[2])->first();

            if (!$user || !$payrollComponent) {
                continue;
            }

            $grouped[$user->id] ??= [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'nik' => $user->nik,
                ],
                'payroll_components' => [],
            ];

            $grouped[$user->id]['payroll_components'][] = [
                'id' => $payrollComponent->id,
                'name' => $payrollComponent->name,
                'type' => $payrollComponent->type->value,
                'current_amount' => (int) $row[3],
                'new_amount' => (int) $row[4],
            ];
        }

        $this->datas = array_values($grouped);
    }
}
