<?php

namespace App\Exports;

use App\Models\Patrol;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
/**
 * Export patrol task report using Blade view for base layout + AfterSheet event
 * to insert images as external URL-based Drawing objects (lightweight, resizable).
 * Images are NOT embedded - they reference S3 URLs directly.
 */
class PatrolTaskExport implements FromView
{
    use Exportable;

    public function __construct(private Patrol $patrol, private string $startDate, private string $endDate, public bool $useSigned = false) {}

    /**
     * @return \Illuminate\Support\Collection
     */
    public function view(): View
    {
        // $media = $this->patrol->users[26]->user->patrolBatches[0]->userPatrolTasks[0]->media[0]->getUrl();
        // dd($media);
        // dd($this->patrol->users[0]->user->patrolBatches->toArray());
        // dd($this->patrol->toArray());
        return view('api.exports.patrol.export', [
            'patrol' => $this->patrol,
            'startDate' => date('d-M-Y', strtotime($this->startDate)),
            'endDate' => date('d-M-Y', strtotime($this->endDate)),
            'useSigned' => $this->useSigned
        ]);
    }

    /**
     * Inject formulas & formatting after Blade view is rendered.
     * NOTE: Row mapping assumes current Blade structure:
     *  - 3 summary rows (Nama Patroli / Map Lokasi / Tanggal)
     *  - 1 header row for location table + N location rows
     *  - 1 header row for task table
     *  - Dynamic rows: user header, batch header, task rows, blank separators
     * If the Blade view changes significantly, adjust the math below.
     */
    // public function registerEvents(): array
    // {
    //     return [
    //         AfterSheet::class => function (AfterSheet $event) {
    //             $sheet = $event->sheet->getDelegate();

    //             // Basic column sizing for existing columns (A-H as rendered by view)
    //             $sheet->getColumnDimension('A')->setWidth(25); // User / summary
    //             $sheet->getColumnDimension('B')->setWidth(22); // Batch / summary value
    //             $sheet->getColumnDimension('C')->setWidth(30); // Task
    //             $sheet->getColumnDimension('D')->setWidth(22); // Lokasi
    //             $sheet->getColumnDimension('E')->setWidth(45); // Laporan Pekerjaan / alamat
    //             $sheet->getColumnDimension('F')->setWidth(18); // Waktu
    //             $sheet->getColumnDimension('G')->setWidth(18); // Map link
    //             $sheet->getColumnDimension('H')->setWidth(50); // Bukti Foto (base64 / fallback)

    //             // Calculate max images across all tasks to set column headers
    //             $maxImages = 0;
    //             foreach ($this->patrol->users as $userPatrol) {
    //                 foreach ($userPatrol->user->patrolBatches as $patrolBatch) {
    //                     foreach ($patrolBatch->userPatrolTasks as $userPatrolTask) {
    //                         $imageCount = $userPatrolTask->media->count();
    //                         if ($imageCount > $maxImages) {
    //                             $maxImages = $imageCount;
    //                         }
    //                     }
    //                 }
    //             }

    //             // Set column width for all image columns dynamically
    //             for ($i = 0; $i < $maxImages; $i++) {
    //                 $col = chr(73 + $i); // I=73, J=74, K=75, etc (ASCII)
    //                 $sheet->getColumnDimension($col)->setWidth(18);
    //             }

    //             // Header for new image columns: find task header row.
    //             $locationsCount = $this->patrol->patrolLocations->count();
    //             $taskHeaderRow = 5 + $locationsCount; // Derived from view layout
                
    //             for ($i = 0; $i < $maxImages; $i++) {
    //                 $col = chr(73 + $i); // I, J, K, L, etc
    //                 $sheet->setCellValue($col . $taskHeaderRow, 'Foto ' . ($i + 1));
    //                 $sheet->getStyle($col . $taskHeaderRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    //                 $sheet->getStyle($col . $taskHeaderRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    //             }

    //             // Begin inserting formulas one row after header.
    //             $currentRow = $taskHeaderRow + 1;

    //             foreach ($this->patrol->users as $userPatrol) {
    //                 // User row (bold row inserted by view) - skip formula insert but adjust styling.
    //                 $lastCol = chr(72 + $maxImages); // Extend to cover all image columns
    //                 $sheet->getStyle('A' . $currentRow . ':' . $lastCol . $currentRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    //                 $currentRow++; // Move to batches

    //                 foreach ($userPatrol->user->patrolBatches as $patrolBatch) {
    //                     // Batch row
    //                     $sheet->getStyle('A' . $currentRow . ':' . $lastCol . $currentRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
    //                     $currentRow++; // Move to tasks

    //                     foreach ($patrolBatch->userPatrolTasks as $userPatrolTask) {
    //                         // Collect ALL image URLs (no limit)
    //                         $urls = [];
    //                         foreach ($userPatrolTask->media as $media) {
    //                             // Use only 'thumb' conversion if available; otherwise original
    //                             $preferred = $media->hasGeneratedConversion('thumb') ? 'thumb' : null;
    //                             if ($this->useSigned) {
    //                                 $urls[] = $preferred
    //                                     ? $media->getTemporaryUrl(now()->addHours(24), $preferred)
    //                                     : $media->getTemporaryUrl(now()->addHours(24));
    //                             } else {
    //                                 $urls[] = $preferred
    //                                     ? $media->getUrl($preferred)
    //                                     : $media->getUrl();
    //                             }
    //                         }

    //                         // Insert IMAGE formula for each URL in respective columns (I, J, K, L, etc)
    //                         foreach ($urls as $index => $url) {
    //                             $col = chr(73 + $index); // I=73, J=74, K=75, etc
    //                             $formula = '=IMAGE("' . str_replace('"', '""', $url) . '")';
    //                             $sheet->setCellValueExplicit($col . $currentRow, $formula, DataType::TYPE_FORMULA);
    //                         }

    //                         // Fill empty cells with '-' if task has fewer images than max
    //                         $imageCount = count($urls);
    //                         for ($i = $imageCount; $i < $maxImages; $i++) {
    //                             $col = chr(73 + $i);
    //                             $sheet->setCellValue($col . $currentRow, '-');
    //                         }

    //                         // Row formatting
    //                         $sheet->getRowDimension($currentRow)->setRowHeight(85);
    //                         $sheet->getStyle('E' . $currentRow)->getAlignment()->setWrapText(true); // Description wrap
                            
    //                         // Extend alignment to cover all image columns dynamically
    //                         $lastCol = chr(72 + $maxImages); // H + maxImages
    //                         $sheet->getStyle('A' . $currentRow . ':' . $lastCol . $currentRow)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
    //                         $currentRow++;
    //                     }

    //                     // Blank separator row after tasks per batch (exists in view) -> skip one
    //                     $currentRow++;
    //                 }

    //                 // Blank separator row after each user (exists in view)
    //                 $currentRow++;
    //             }
    //         },
    //     ];
    // }
}
