<?php

namespace App\Exports\User;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UserExport implements FromQuery, WithHeadings, WithMapping, WithColumnFormatting, WithStyles, ShouldAutoSize, WithColumnWidths
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
            'ID',
            'Company',
            'Branch',
            'Department & Position',
            'NIK',
            'Name',
            'Last Name',
            'Email',
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
            // 'Basic Salary',
            'Overtime Setting',
            'Bank Name',
            'Bank Account Number',
            'Bank Account Holder',
            'NPWP',
            'PTKP Status',
            'Tax Method',
            'Tax Salary',
            'Beginning Netto',
            'PPH 21 Paid',
            // 'BPJS Kesehatan Plafond',
            'BPJS Kesehatan Number',
            'BPJS Kesehatan Date',
            'BPJS Kesehatan Family Number',
            // 'BPJS Ketenagakerjaan Plafond',
            'BPJS Ketenagakerjaan Number',
            'BPJS Ketenagakerjaan Date',
            'BPJS Kesehatan Paid By',
            'JHT Paid By',
            'Jaminan Pensiun Paid By',
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
        $positions = "";
        foreach ($user->positions as $p) {
            $positions .= $p->department->name . '/' . $p->position->name . ', ';
        }
        $positions = rtrim($positions, ', ');

        return [
            $user->id,
            $user->branch?->company?->name ?? '',
            $user->branch?->name ?? '',
            $positions,
            $user->nik,
            $user->name,
            $user->last_name,
            $user->email,
            $user->phone,
            $user->gender?->value ?? '',
            $user->join_date ? date('d-m-Y', strtotime($user->join_date)) : '',
            $user->sign_date ? date('d-m-Y', strtotime($user->sign_date)) : '',
            $user->resign_date ? date('d-m-Y', strtotime($user->resign_date)) : '',
            $user->detail?->no_ktp,
            $user->detail?->kk_no,
            $user->detail?->address,
            $user->detail?->address_ktp,
            $user->detail?->postal_code,
            $user->detail?->employment_status?->value ?? '',
            $user->detail?->passport_no,
            $user->detail?->passport_expired ? date('d-m-Y', strtotime($user->detail?->passport_expired)) : '',
            $user->detail?->birth_place,
            $user->detail?->birthdate ? date('d-m-Y', strtotime($user->detail?->birthdate)) : '',
            $user->detail?->marital_status?->value ?? '',
            $user->detail?->blood_type?->value ?? '',
            $user->detail?->blood_rhesus?->value ?? '',
            $user->detail?->religion?->value ?? '',
            // $user->payrollInfo?->basic_salary,
            $user->payrollInfo?->overtime_setting?->value ?? '',
            $user->payrollInfo?->bank_name,
            $user->payrollInfo?->bank_account_number,
            $user->payrollInfo?->bank_account_holder,
            $user->payrollInfo?->npwp,
            $user->payrollInfo?->ptkp_status?->value ?? '',
            $user->payrollInfo?->tax_method?->value ?? '',
            $user->payrollInfo?->tax_salary?->value ?? '',
            $user->payrollInfo?->beginning_netto,
            $user->payrollInfo?->pph21_paid,
            // $user->userBpjs?->upah_bpjs_kesehatan,
            $user->userBpjs?->bpjs_kesehatan_no,
            $user->userBpjs?->bpjs_kesehatan_date ? date('d-m-Y', strtotime($user->userBpjs?->bpjs_kesehatan_date)) : '',
            $user->userBpjs?->bpjs_kesehatan_family_no,
            // $user->userBpjs?->upah_bpjs_ketenagakerjaan,
            $user->userBpjs?->bpjs_ketenagakerjaan_no,
            $user->userBpjs?->bpjs_ketenagakerjaan_date ? date('d-m-Y', strtotime($user->userBpjs?->bpjs_ketenagakerjaan_date)) : '',
            $user->userBpjs?->bpjs_kesehatan_cost?->value ?? '',
            $user->userBpjs?->jht_cost?->value ?? '',
            $user->userBpjs?->jaminan_pensiun_date?->value ?? '',
        ];
    }

    public function columnWidths(): array
    {
        return [
            // 'E' => 30,
            'I' => 30,
            'N' => 30,
            'O' => 30,
            // 'R' => 30,
            // 'T' => 30,
            // 'AD' => 30,
            // 'AF' => 30,
            // 'AL' => 30,
            // 'AN' => 30,
            // 'AO' => 30,
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_GENERAL,
            'I' => NumberFormat::FORMAT_GENERAL,
            'N' => NumberFormat::FORMAT_GENERAL,
            'O' => NumberFormat::FORMAT_GENERAL,
            'R' => NumberFormat::FORMAT_GENERAL,
            'T' => NumberFormat::FORMAT_GENERAL,
            'AD' => NumberFormat::FORMAT_GENERAL,
            'AF' => NumberFormat::FORMAT_GENERAL,
            'AL' => NumberFormat::FORMAT_GENERAL,
            'AN' => NumberFormat::FORMAT_GENERAL,
            'AO' => NumberFormat::FORMAT_GENERAL,
        ];
    }
}
