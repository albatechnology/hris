<?php

namespace App\Exports\User;

use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsersImportTemplate implements FromArray, WithHeadings, WithStyles, Responsable
{
    use Exportable;

    private $fileName = 'template-import-users.xlsx';

    /**
     * @return \Illuminate\Support\Collection
     */
    public function array(): array
    {
        return [
            [
                'SWSJS001',
                'SWSJS002',
                null,
                null,
                '9',
                '6',
                '13',
                '10',
                '',
                null,
                'O-99-010999-99931',
                'Aldi',
                'aldikarolah@gmail.com',
                'secret',
                '0818172733748',
                'male',
                '10-02-2025',
                '10-02-2025',
                '',
                '3603821211900009',
                '3603834211900001',
                'Kp. Pegandon No. 1',
                'Kp. Pegandon No. 1',
                '15711',
                'permanent',
                '',
                '',
                'Kendal',
                '01-11-2000',
                'single',
                'B',
                '-',
                'konghucu',
                'eligible',
                'BCA',
                '23482038520',
                'Aldi Karolah',
                '',
                '',
                '',
                '09.245.934.2-011.000',
                'K/0',
                'gross',
                'taxable',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ],
            [
                'SWSJS001',
                'SWSJS002',
                'SWSJS003',
                'SWSJS004',
                '9',
                '6',
                '13',
                '10',
                '',
                null,
                'O-99-010999-99931',
                'Karolah',
                'karolahaldi@gmail.com',
                'verysecret',
                '0818172733799',
                'female',
                '10-02-2025',
                '10-02-2025',
                '',
                '3603821211900111',
                '3603834211900222',
                'Kp. Pegandon No. 2',
                'Kp. Pegandon No. 2',
                '15711',
                'contract',
                '',
                '',
                'Kebumen',
                '01-11-2000',
                'widower',
                'A',
                '+',
                'islam',
                'eligible',
                'BCA',
                '23482038520',
                'Karolah Aldi',
                '',
                '',
                '',
                '09.245.934.2-011.111',
                'K/1',
                'gross',
                'taxable',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ]
        ];
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
            'Schedule ID',
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
            'BPJS Kesehatan Number',
            'BPJS Kesehatan Date',
            'BPJS Kesehatan Family Number',
            'BPJS Ketenagakerjaan Number',
            'BPJS Ketenagakerjaan Date',
            'Jaminan Pensiun Date',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text.
            1    => ['font' => ['bold' => true]],
        ];
    }
}
