<?php

namespace App\Exports;

use App\Models\Patrol;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class PatrolTaskExport implements FromView, WithEvents
{
    use Exportable;

    public function __construct(private Patrol $patrol, private string $date) {}

    /**
     * @return \Illuminate\Support\Collection
     */
    public function view(): View
    {
        return view('api.exports.patrol.export', [
            'patrol' => $this->patrol,
            'date' => $this->date
        ]);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getColumnDimension('A')->setWidth(30);
                $sheet->getColumnDimension('B')->setWidth(25);
                $sheet->getColumnDimension('D')->setWidth(25);
                $sheet->getColumnDimension('E')->setWidth(25);

                // Set lebar kolom C
                $sheet->getColumnDimension('C')->setWidth(50);

                // Wrap text kolom C
                $sheet->getStyle('C')->getAlignment()->setWrapText(true);

                // Set vertical alignment tengah
                $sheet->getStyle('C')->getAlignment()->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            },
        ];
    }
}
