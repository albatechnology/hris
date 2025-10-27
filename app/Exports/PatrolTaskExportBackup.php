<?php

namespace App\Exports;

use App\Models\Patrol;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class PatrolTaskExportBackup implements FromView, WithEvents
{
    use Exportable;

    private $imageCache = [];

    public function __construct(private Patrol $patrol, private string $startDate, private string $endDate) {}

    /**
     * @return \Illuminate\Support\Collection
     */
    public function view(): View
    {
        // Pre-cache image URLs
        foreach ($this->patrol->users as $userPatrol) {
            foreach ($userPatrol->user->patrolBatches as $patrolBatch) {
                foreach ($patrolBatch->userPatrolTasks as $userPatrolTask) {
                    foreach ($userPatrolTask->media as $media) {
                        if ($media->hasGeneratedConversion('thumb')) {
                            $url = $media->getUrl('thumb');
                        } else {
                            $url = $media->getUrl();
                        }
                        // Store in cache
                        $this->imageCache[$media->id] = $url;
                    }
                }
            }
        }

        return view('api.exports.patrol.export', [
            'patrol' => $this->patrol,
            'startDate' => date('d-M-Y', strtotime($this->startDate)),
            'endDate' => date('d-M-Y', strtotime($this->endDate)),
            'imageCache' => $this->imageCache
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $workSheet = $event->sheet->getDelegate();

                // Enable image display
                $workSheet->getParent()->getDefaultStyle()->getAlignment()->setWrapText(true);

                // Set column dimensions
                $workSheet->getColumnDimension('H')->setWidth(50); // Kolom gambar

                // Set row height for image rows
                foreach ($workSheet->getRowIterator() as $row) {
                    $rowIndex = $row->getRowIndex();
                    if ($workSheet->getCellByColumnAndRow(8, $rowIndex)->getValue() !== null) {
                        $workSheet->getRowDimension($rowIndex)->setRowHeight(120);
                    }
                }
            },
        ];
    }

    // public function registerEvents(): array
    // {
    //     return [
    //         AfterSheet::class => function (AfterSheet $event) {
    //             $sheet = $event->sheet->getDelegate();
    //             $sheet->getColumnDimension('A')->setWidth(30);
    //             $sheet->getColumnDimension('B')->setWidth(25);
    //             $sheet->getColumnDimension('D')->setWidth(25);
    //             $sheet->getColumnDimension('E')->setWidth(25);

    //             // Set lebar kolom C
    //             $sheet->getColumnDimension('C')->setWidth(50);

    //             // Wrap text kolom C
    //             $sheet->getStyle('C')->getAlignment()->setWrapText(true);

    //             // Set vertical alignment tengah
    //             $sheet->getStyle('C')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
    //         },
    //     ];
    // }
}
