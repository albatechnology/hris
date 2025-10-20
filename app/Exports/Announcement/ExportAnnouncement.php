<?php

namespace App\Exports\Announcement;

use App\Http\Requests\Api\Announcement\ExportRequest;
use App\Models\Announcement;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportAnnouncement implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    use Exportable;

    public function __construct(private ExportRequest $request) {}

    public function query()
    {
        $companyId = $this->request['filter']['company_id'] ?? null;
        $createdStartDate = $this->request['filter']['created_start_date'] ?? null;
        $createdInEndDate = $this->request['filter']['created_end_date'] ?? null;

        return Announcement::tenanted()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->when($createdStartDate, fn($q) => $q->whereDate('created_at', '>=', $createdStartDate))
            ->when($createdInEndDate, fn($q) => $q->whereDate('created_at', '<=', $createdInEndDate))
            ->with([
                'createdBy' => fn($q) => $q->withTrashed()->select('id', 'name'),
                'media'
            ]);
    }

    public function headings(): array
    {
        return [
            'ID',
            'Created By',
            'Title',
            'Content',
            'Created At',
            'Updated At',
        ];
    }

    public function map($announcement): array
    {
        return [
            $announcement->id,
            $announcement->createdBy?->name,
            $announcement->title,
            $announcement->content,
            $announcement->created_at,
            $announcement->updated_at,
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
