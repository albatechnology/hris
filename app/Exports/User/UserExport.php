<?php

namespace App\Exports\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserExport extends DefaultValueBinder implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting, WithStyles, ShouldAutoSize, WithCustomValueBinder
{
    use Exportable;

    public function __construct(private Builder $query) {}

    public function query()
    {
        return $this->query;
    }

    public function headings(): array
    {
        return [
            'Supervisor NIK 1',
            'Supervisor NIK 2',
            'Supervisor NIK 3',
            'Supervisor NIK 4',
            'Role ID',
            'Branch ID',
            'Department ID',
            'Position ID',
            'Live Attendance ID',
            'NIK',
            'Name',
            'Email',
            'Password',
            'Phone',
            'Gender',
            'Join Date',
            'Sign Date',
            'Resign Date',
            'KTP Number',
            'KK Number',
            'Address KTP',
            'Address',
            'Postal Code',
            'Employment Status',
            'Passport Number',
            'Passport Expired',
            'Birth Place',
            'Birthdate',
            'Marital Status',
            'Blood Type',
            'Blood Rhesus',
            'Religion',
            'Basic Salary',
            'Overtime Setting',
            'Bank Name',
            'Bank Account Number',
            'Bank Account Holder',
            'Secondary Bank Name',
            'Secondary Bank Account Number',
            'Secondary Bank Account Holder',
            'NPWP',
            'PTKP Status',
            'Tax Method',
            'Tax Salary',
            'Beginning Netto',
            'PPH 21 Paid',
            'BPJS Kesehatan Date',
            'BPJS Kesehatan Number',
            'BPJS Kesehatan Family Number',
            'BPJS Ketenagakerjaan Number',
            'BPJS Ketenagakerjaan Date',
            'Jaminan Pensiun Date',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    /**
     * @param User $user
     */
    public function map($user): array
    {
        $supervisorsNik = $user->supervisors?->sortBy('order')
            ->map(fn($item) => $item['supervisor']['nik'] ?? null)
            ->pad(4, null)
            ->values();

        return [
            ...$supervisorsNik,
            $user->roles[0]?->id ?? null,
            $user->branch_id,
            $user->positions[0]?->department_id ?? null,
            $user->positions[0]?->position_id ?? null,
            $user->live_attendance_id,
            $user->nik,
            $user->name,
            $user->email,
            null,
            $user->phone,
            $user->gender?->value ?? null,
            $user->join_date ? date('d-m-Y', strtotime($user->join_date)) : null,
            $user->sign_date ? date('d-m-Y', strtotime($user->sign_date)) : null,
            $user->resign_date ? date('d-m-Y', strtotime($user->resign_date)) : null,
            (string)$user->detail?->no_ktp,
            (string)$user->detail?->kk_no,
            $user->detail?->address_ktp,
            $user->detail?->address,
            $user->detail?->postal_code,
            $user->detail?->employment_status?->value ?? null,
            (string)$user->detail?->passport_no,
            $user->detail?->passport_expired ? date('d-m-Y', strtotime($user->detail?->passport_expired)) : null,
            $user->detail?->birth_place,
            $user->detail?->birthdate ? date('d-m-Y', strtotime($user->detail?->birthdate)) : null,
            $user->detail?->marital_status?->value ?? null,
            $user->detail?->blood_type?->value ?? null,
            $user->detail?->blood_rhesus?->value ?? null,
            $user->detail?->religion?->value ?? null,
            $user->payrollInfo?->basic_salary,
            $user->payrollInfo?->overtime_setting?->value ?? null,
            $user->payrollInfo?->bank_name,
            $user->payrollInfo?->bank_account_number,
            $user->payrollInfo?->bank_account_holder,
            $user->payrollInfo?->secondary_bank_name,
            $user->payrollInfo?->secondary_bank_account_number,
            $user->payrollInfo?->secondary_bank_account_holder,
            $user->payrollInfo?->npwp,
            $user->payrollInfo?->ptkp_status?->value ?? null,
            $user->payrollInfo?->tax_method?->value ?? null,
            $user->payrollInfo?->tax_salary?->value ?? null,
            $user->payrollInfo?->beginning_netto,
            $user->payrollInfo?->pph21_paid,
            $user->userBpjs?->bpjs_kesehatan_date ? date('d-m-Y', strtotime($user->userBpjs?->bpjs_kesehatan_date)) : null,
            (string)$user->userBpjs?->bpjs_kesehatan_no,
            (string)$user->userBpjs?->bpjs_kesehatan_family_no,
            (string)$user->userBpjs?->bpjs_ketenagakerjaan_no,
            $user->userBpjs?->bpjs_ketenagakerjaan_date ? date('d-m-Y', strtotime($user->userBpjs?->bpjs_ketenagakerjaan_date)) : null,
            $user->userBpjs?->jaminan_pensiun_date,
        ];
    }

    // public function columnWidths(): array
    // {
    //     return [
    //         // 'E' => 30,
    //         // 'I' => 30,
    //         // 'N' => 30,
    //         // 'O' => 30,
    //         // 'R' => 30,
    //         // 'T' => 30,
    //         // 'AD' => 30,
    //         // 'AF' => 30,
    //         // 'AL' => 30,
    //         // 'AN' => 30,
    //         // 'AO' => 30,
    //     ];
    // }

    public function bindValue(Cell $cell, $value)
    {
        if (is_numeric($value) && strlen($value) >= 15) {
            // Paksa sebagai string TEXT agar tidak jadi notasi ilmiah
            $cell->setValueExplicit($value, DataType::TYPE_STRING);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        return [
            'A' => NumberFormat::FORMAT_GENERAL,
            'B' => NumberFormat::FORMAT_GENERAL,
            'C' => NumberFormat::FORMAT_GENERAL,
            'D' => NumberFormat::FORMAT_GENERAL,
            'K' => NumberFormat::FORMAT_GENERAL,
            'O' => NumberFormat::FORMAT_GENERAL,
            'S' => NumberFormat::FORMAT_TEXT,
            'T' => NumberFormat::FORMAT_TEXT,
            'U' => NumberFormat::FORMAT_GENERAL,
            'X' => NumberFormat::FORMAT_GENERAL,
            'Z' => NumberFormat::FORMAT_GENERAL,
            'AK' => NumberFormat::FORMAT_GENERAL,
            'AN' => NumberFormat::FORMAT_GENERAL,
            'AP' => NumberFormat::FORMAT_GENERAL,
            'AW' => NumberFormat::FORMAT_GENERAL,
            'AX' => NumberFormat::FORMAT_GENERAL,
            'AY' => NumberFormat::FORMAT_GENERAL,
        ];
    }
}
