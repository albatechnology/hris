<?php

namespace App\Exports\GuestBook;

use App\Http\Requests\Api\GuestBook\ExportRequest;
use App\Models\GuestBook;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportGuestBook implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    public function __construct(private ExportRequest $request) {}

    public function query()
    {
        $companyId = $this->request['filter']['company_id'] ?? null;
        $branchId = $this->request['filter']['branch_id'] ?? null;
        $checkInStartDate = $this->request['filter']['check_in_start_date'] ?? null;
        $checkInEndDate = $this->request['filter']['check_in_end_date'] ?? null;

        return GuestBook::tenanted()
            ->when($companyId, fn($q) => $q->whereHas('branch', fn($q) => $q->where('company_id', $companyId)))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($checkInStartDate, fn($q) => $q->whereDate('created_at', '>=', $checkInStartDate))
            ->when($checkInEndDate, fn($q) => $q->whereDate('created_at', '<=', $checkInEndDate))
            ->with('branch', fn($q) => $q->withTrashed()->select('id', 'name'))
            ->with('user', fn($q) => $q->withTrashed()->select('id', 'name'))
            ->with('checkOutBy', fn($q) => $q->withTrashed()->select('id', 'name'));
    }

    public function headings(): array
    {
        return [
            'ID',
            'Branch',
            'Name',
            'Address',
            'Room',
            'Location Destination',
            'Person Destination',
            'Vehicle Number',
            'Description',
            'Check In By',
            'Check In At',
            'Check Out By',
            'Check Out At',
        ];
    }

    public function map($guestBook): array
    {
        return [
            $guestBook->id,
            $guestBook->branch?->name ?? '',
            $guestBook->name,
            $guestBook->address,
            $guestBook->room,
            $guestBook->location_destination,
            $guestBook->person_destination,
            $guestBook->vehicle_number,
            $guestBook->description,
            $guestBook->user?->name ?? '',
            $guestBook->created_at,
            $guestBook->checkOutBy?->name ?? '',
            $guestBook->check_out_at,
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
