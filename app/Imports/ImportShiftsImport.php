<?php

namespace App\Imports;

use App\Models\Schedule;
use App\Models\Shift;
use App\Rules\CompanyTenantedRule;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithLimit;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class ImportShiftsImport implements ToCollection, WithStartRow, WithValidation, WithLimit
{
    public function __construct(public Schedule $schedule) {}

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function collection(Collection $rows)
    {
        if (count($rows[0]) > 0) {
            $this->schedule->shifts()->sync([]);

            $order = 1;
            foreach ($rows[0] ?? [] as $shiftId) {
                $this->schedule->shifts()->attach($shiftId, ['order' => $order++]);
            }
        }
    }

    public function startRow(): int
    {
        return 2;
    }

    public function limit(): int
    {
        return 1;
    }

    public function rules(): array
    {

        return [
            '0' => ['required', new CompanyTenantedRule(Shift::class, 'Shift not found')],
        ];
    }
}
