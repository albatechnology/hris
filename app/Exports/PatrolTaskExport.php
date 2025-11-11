<?php

namespace App\Exports;

use App\Models\Patrol;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

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
