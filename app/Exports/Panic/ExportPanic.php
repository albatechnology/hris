<?php

namespace App\Exports\Panic;

use App\Http\Requests\Api\Panic\ExportRequest;
use App\Models\Panic;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportPanic implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize, WithDrawings
{
    use Exportable;

    protected Collection $images;

    public function __construct(private ExportRequest $request) {}

    public function query()
    {
        $companyId = $this->request['filter']['company_id'] ?? null;
        $branchId = $this->request['filter']['branch_id'] ?? null;
        $createdStartDate = $this->request['filter']['created_start_date'] ?? null;
        $createdEndDate = $this->request['filter']['created_end_date'] ?? null;

        $query = Panic::tenanted()
            ->when($companyId, fn($q) => $q->whereHas('branch', fn($q) => $q->where('company_id', $companyId)))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($createdStartDate, fn($q) => $q->whereDate('created_at', '>=', $createdStartDate))
            ->when($createdEndDate, fn($q) => $q->whereDate('created_at', '<=', $createdEndDate))
            ->with([
                'branch' => fn($q) => $q->withTrashed()->select('id', 'name'),
                'user' => fn($q) => $q->withTrashed()->select('id', 'name'),
                'solvedBy' => fn($q) => $q->withTrashed()->select('id', 'name'),
                'media'
            ]);

        // simpan hasil untuk drawings()
        $this->images = $query->get();

        return $query;
    }

    public function headings(): array
    {
        return [
            'ID',
            'Branch',
            'User',
            'LatLng Coordinate',
            'Status',
            'Description',
            'Created At',
            'Solved By',
            'Solved At',
            'Solved LatLng Coordinate',
            'Solved Description',
            'Photo',
        ];
    }

    public function map($panic): array
    {
        return [
            $panic->id,
            $panic->branch?->name ?? '',
            $panic->user?->name ?? '',
            '<a href="https://www.google.com/maps/search/' . $panic->lat . ',' . $panic->lng . '">Lihat Lokasi</a>',
            $panic->status->value,
            $panic->description,
            $panic->created_at,
            $panic->solvedBy?->name,
            $panic->solved_at,
            '<a href="https://www.google.com/maps/search/' . $panic->solved_lat . ',' . $panic->solved_lng . '">Lihat Lokasi</a>',
            $panic->solved_description,
            ''
        ];
    }

    public function drawings(): array
    {
        $drawings = [];
        $startColumn = 'L'; // kolom awal untuk gambar
        foreach ($this->images as $index => $guestBook) {
            $row = $index + 2; // karena row 1 = heading

            foreach ($guestBook->media as $mediaIndex => $media) {
                $col = chr(ord($startColumn) + $mediaIndex); // N, O, P, dst
                $url = $media->getUrl(); // public S3 URL

                // buat file temp
                $tempPath = tempnam(sys_get_temp_dir(), 'img_');
                try {
                    file_put_contents($tempPath, file_get_contents($url));
                } catch (\Throwable $e) {
                    continue;
                }

                $drawing = new Drawing();
                $drawing->setName("Photo {$guestBook->id} - {$mediaIndex}");
                $drawing->setDescription('Panic photo');
                $drawing->setPath($tempPath);
                $drawing->setHeight(60); // tinggi gambar (px)
                $drawing->setCoordinates($col . $row);

                // offset supaya center di cell
                $drawing->setOffsetX(5);
                $drawing->setOffsetY(5);

                $drawings[] = $drawing;
            }
        }

        return $drawings;
    }

    public function styles(Worksheet $sheet)
    {
        // atur tinggi baris & lebar kolom supaya gambar rapi
        foreach (range(2, count($this->images) + 1) as $row) {
            $sheet->getRowDimension($row)->setRowHeight(50);
        }

        // kolom untuk gambar
        foreach (['L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U'] as $col) {
            $sheet->getColumnDimension($col)->setWidth(18);
        }

        // heading tebal
        return [1 => ['font' => ['bold' => true]]];
    }
}
