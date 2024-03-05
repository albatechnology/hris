<?php

namespace App\Exports;

use App\Enums\ImportType;
use Maatwebsite\Excel\Concerns\FromCollection;

class ImportSampleExport implements FromCollection
{
    public function __construct(protected ImportType $importType)
    {
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $exporter = $this->importType->getExporter();
        return $exporter::getSample();
    }
}
