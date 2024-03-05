<?php

namespace App\Http\Controllers;

use App\Enums\ImportType;
use App\Exports\ImportSampleExport;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function sample(string $importType): RedirectResponse | BinaryFileResponse
    {
        $importType = ImportType::tryFrom($importType);
        if (!$importType) {
            return redirect()->back()->with('error', 'Data import not found');
        }

        return Excel::download(new ImportSampleExport($importType), $importType->getDescription() . '.csv');
    }
}
