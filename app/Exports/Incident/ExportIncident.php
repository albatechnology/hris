<?php

namespace App\Exports\Incident;

use App\Http\Requests\Api\Incident\ExportRequest;
use App\Models\Incident;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportIncident implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    public function __construct(private ExportRequest $request) {}

    public function query()
    {
        $companyId = $this->request['filter']['company_id'] ?? null;
        $branchId = $this->request['filter']['branch_id'] ?? null;
        $createdAtStartDate = $this->request['filter']['created_at_start_date'] ?? null;
        $createdAtEndDate = $this->request['filter']['created_at_end_date'] ?? null;

        return Incident::tenanted()
            ->when($companyId, fn($q) => $q->whereHas('branch', fn($q) => $q->where('company_id', $companyId)))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($createdAtStartDate, fn($q) => $q->whereDate('created_at', '>=', $createdAtStartDate))
            ->when($createdAtEndDate, fn($q) => $q->whereDate('created_at', '<=', $createdAtEndDate))
            ->with('branch', fn($q) => $q->withTrashed()->select('id', 'name'))
            ->with('user', fn($q) => $q->withTrashed()->select('id', 'name'))
            ->with('incidentType', fn($q) => $q->withTrashed()->select('id', 'name'))
            ->with('media');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Branch',
            'User',
            'Type',
            'Description',
            'Created At',
            'Files',
        ];
    }

    public function map($incident): array
    {
        $files = $incident->media->map(fn($media) => '=HYPERLINK("' . $media->original_url . '", "Lihat File")');

        return [
            $incident->id,
            $incident->branch?->name ?? '',
            $incident->user?->name ?? '',
            $incident->incidentType?->name ?? '',
            $incident->description,
            $incident->created_at,
            ...$files,
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
