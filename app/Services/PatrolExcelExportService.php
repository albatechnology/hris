<?php

namespace App\Services;

use App\Models\Patrol;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk export Patrol ke Excel dengan gambar dari AWS S3
 *
 * Teknik optimasi:
 * 1. Download thumbnail dari S3 dan embed sebagai base64
 * 2. Resize on-the-fly jika conversion belum ada
 * 3. Skip gambar yang terlalu besar (>150KB)
 * 4. Chunking untuk data besar
 * 5. Timeout handling untuk S3 download
 */
class PatrolExcelExportService
{
    private int $maxImagesPerTask;
    private int $chunkSize;
    private int $downloadTimeout;
    private int $maxImageSize;

    public function __construct(
        int $maxImagesPerTask = 1,
        int $chunkSize = 500,
        int $downloadTimeout = 15,      // 15 detik per gambar
        int $maxImageSize = 153600      // 150KB max per gambar
    ) {
        $this->maxImagesPerTask = $maxImagesPerTask;
        $this->chunkSize = $chunkSize;
        $this->downloadTimeout = $downloadTimeout;
        $this->maxImageSize = $maxImageSize;
    }

    /**
     * Generate Excel HTML content
     */
    public function generateExcelHtml(Patrol $patrol, string $startDate, string $endDate): string
    {
        $this->loadPatrolRelations($patrol, $startDate, $endDate);

        $html = $this->buildHtmlHeader();
        $html .= $this->buildPatrolInfoTable($patrol, $startDate, $endDate);
        $html .= $this->buildPatrolLocationsTable($patrol);
        $html .= $this->buildPatrolTasksTable($patrol);
        $html .= $this->buildHtmlFooter();

        return $html;
    }

    /**
     * Load relasi Patrol dengan batasan gambar
     */
    private function loadPatrolRelations(Patrol $patrol, string $startDate, string $endDate): void
    {
        $patrol->load([
            'patrolLocations' => function ($q) {
                $q->select('id', 'patrol_id', 'branch_location_id', 'description')
                    ->with([
                        'branchLocation' => fn($q) => $q->select('id', 'name', 'lat', 'lng', 'address'),
                        'tasks' => fn($q) => $q->select('id', 'patrol_location_id', 'name', 'description')
                    ]);
            },
            'users.user' => function ($q) use ($patrol, $startDate, $endDate) {
                $q->select('id', 'name', 'nik')
                    ->with(['patrolBatches' => function ($batchQuery) use ($patrol, $startDate, $endDate) {
                        $batchQuery->where('patrol_id', $patrol->id)
                            ->whereDate('datetime', '>=', $startDate)
                            ->whereDate('datetime', '<=', $endDate)
                            ->with(['userPatrolTasks' => function ($taskQuery) {
                                $taskQuery->with([
                                    'patrolTask.patrolLocation.branchLocation:id,name',
                                    // Batasi jumlah media di query level
                                    'media' => fn($m) => $m->oldest()->limit($this->maxImagesPerTask)
                                ]);
                            }]);
                    }]);
            },
        ]);
    }

    /**
     * Build HTML header
     */
    private function buildHtmlHeader(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
            font-size: 10pt;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px;
            vertical-align: top;
        }
        th {
            background-color: #b4c7dc;
            font-weight: bold;
            text-align: left;
        }
        .user-header {
            background-color: #ffdbb6;
            font-weight: bold;
        }
        .batch-header {
            background-color: #ffb66c;
            font-weight: bold;
        }
        img {
            max-width: 100px;
            max-height: 100px;
            display: block;
            margin: 2px auto;
        }
        a {
            color: #0066cc;
            text-decoration: none;
        }
        .image-cell {
            text-align: center;
            padding: 4px;
        }
    </style>
</head>
<body>
HTML;
    }

    /**
     * Build patrol info table
     */
    private function buildPatrolInfoTable(Patrol $patrol, string $startDate, string $endDate): string
    {
        $formattedStart = date('d-M-Y', strtotime($startDate));
        $formattedEnd = date('d-M-Y', strtotime($endDate));
        $mapUrl = "https://www.google.com/maps/search/{$patrol->lat},{$patrol->lng}";
        $patrolName = e($patrol->name);

        return <<<HTML
<table>
    <tr>
        <th style="width: 200px;">Nama Patroli</th>
        <td>{$patrolName}</td>
    </tr>
    <tr>
        <th>Map Lokasi</th>
        <td><a href="{$mapUrl}">Lihat Lokasi</a></td>
    </tr>
    <tr>
        <th>Tanggal</th>
        <td>{$formattedStart} - {$formattedEnd}</td>
    </tr>
</table>
<br/>
HTML;
    }

    /**
     * Build patrol locations table
     */
    private function buildPatrolLocationsTable(Patrol $patrol): string
    {
        $html = <<<HTML
<table>
    <thead>
        <tr>
            <th style="width: 150px;">Lokasi Patroli</th>
            <th style="width: 120px;">Map Lokasi</th>
            <th style="width: 200px;">Alamat</th>
            <th>Task</th>
        </tr>
    </thead>
    <tbody>
HTML;

        foreach ($patrol->patrolLocations as $location) {
            $branchName = e($location->branchLocation->name ?? '-');
            $address = e($location->branchLocation->address ?? '-');
            $lat = $location->branchLocation->lat ?? 0;
            $lng = $location->branchLocation->lng ?? 0;
            $mapUrl = "https://www.google.com/maps/search/{$lat},{$lng}";

            $tasks = '';
            if ($location->tasks->isNotEmpty()) {
                $tasks = '<ol style="margin: 0; padding-left: 20px;">';
                foreach ($location->tasks as $task) {
                    $tasks .= '<li>' . e($task->name) . '</li>';
                }
                $tasks .= '</ol>';
            } else {
                $tasks = '-';
            }

            $html .= <<<HTML
        <tr>
            <td>{$branchName}</td>
            <td><a href="{$mapUrl}">Lihat Lokasi</a></td>
            <td>{$address}</td>
            <td>{$tasks}</td>
        </tr>
HTML;
        }

        $html .= '</tbody></table><br/>';
        return $html;
    }

    /**
     * Build patrol tasks table
     */
    private function buildPatrolTasksTable(Patrol $patrol): string
    {
        $html = <<<HTML
<table>
    <thead>
        <tr>
            <th style="width: 150px;">User</th>
            <th style="width: 130px;">Patroli Batch</th>
            <th style="width: 120px;">Task</th>
            <th style="width: 120px;">Lokasi</th>
            <th style="width: 200px;">Laporan Pekerjaan</th>
            <th style="width: 130px;">Waktu Pengerjaan</th>
            <th style="width: 100px;">Map Lokasi</th>
            <th style="width: 120px;">Bukti Foto</th>
        </tr>
    </thead>
    <tbody>
HTML;

        foreach ($patrol->users as $userPatrol) {
            if (!$userPatrol->user) continue;

            $userName = e($userPatrol->user->name ?? '-');

            // User header row
            $html .= <<<HTML
        <tr>
            <td class="user-header" colspan="8">{$userName}</td>
        </tr>
HTML;

            foreach ($userPatrol->user->patrolBatches as $batch) {
                $batchDatetime = e($batch->datetime ?? '-');

                // Batch header row
                $html .= <<<HTML
        <tr>
            <td></td>
            <td class="batch-header">{$batchDatetime}</td>
            <td class="batch-header" colspan="6"></td>
        </tr>
HTML;

                // Task rows
                $tasks = $batch->userPatrolTasks;
                foreach ($tasks->chunk($this->chunkSize) as $taskChunk) {
                    foreach ($taskChunk as $task) {
                        $html .= $this->buildTaskRow($task);
                    }
                }
            }
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * Build single task row
     */
    private function buildTaskRow($task): string
    {
        $taskName = e($task->patrolTask?->name ?? '-');
        $locationName = e($task->patrolTask?->patrolLocation?->branchLocation?->name ?? '-');
        $description = nl2br(e($task->description ?? '-'));
        $datetime = e($task->datetime ?? '-');
        $mapUrl = "https://www.google.com/maps/search/{$task->lat},{$task->lng}";

        // Build image HTML dari S3
        $imageHtml = $this->buildImageHtmlFromS3($task->media);
        // echo "IMAGE HTML:".$imageHtml;

        return <<<HTML
        <tr>
            <td></td>
            <td></td>
            <td>{$taskName}</td>
            <td>{$locationName}</td>
            <td>{$description}</td>
            <td>{$datetime}</td>
            <td><a href="{$mapUrl}">Lihat Lokasi</a></td>
            <td class="image-cell">{$imageHtml}</td>
        </tr>
HTML;
    }

    /**
     * Build HTML untuk gambar dari S3 (embed base64)
     */
    private function buildImageHtmlFromS3($media): string
    {
        if ($media->isEmpty()) {
            return '-';
        }

        $images = [];
        $processedCount = 0;

        foreach ($media as $mediaItem) {
            if ($processedCount >= $this->maxImagesPerTask) {
                break;
            }

            $imageHtml = $this->downloadAndEmbedFromS3($mediaItem);
            if ($imageHtml) {
                $images[] = $imageHtml;
                $processedCount++;
            }
        }

        return !empty($images) ? implode('<br/>', $images) : '-';
    }

    /**
     * Download gambar dari S3 dan embed sebagai base64
     */
    private function downloadAndEmbedFromS3($mediaItem): ?string
    {
        try {
            // Prioritas: xls_thumb → thumb → original
            $url = $this->getBestImageUrl($mediaItem);

            Log::info("Downloading image from S3: {$url}");

            // Download dari S3 dengan timeout
            $response = Http::timeout($this->downloadTimeout)
                ->retry(2, 1000) // Retry 2x jika gagal
                ->get($url);

            if (!$response->successful()) {
                Log::warning("Failed to download from S3 (HTTP {$response->status()}): {$url}");
                return $this->buildImageLinkFallback($mediaItem);
            }

            $imageData = $response->body();
            $fileSize = strlen($imageData);

            // Validasi ukuran
            if ($fileSize > $this->maxImageSize) {
                Log::warning("Image too large ({$fileSize} bytes), skipping: {$url}");
                return $this->buildImageLinkFallback($mediaItem);
            }

            if ($fileSize < 100) {
                Log::warning("Image too small ({$fileSize} bytes), possibly corrupted: {$url}");
                return $this->buildImageLinkFallback($mediaItem);
            }

            // Encode ke base64
            $base64 = base64_encode($imageData);
            $mimeType = $this->detectMimeType($url, $mediaItem);

            Log::info("Successfully embedded image: {$url} ({$fileSize} bytes)");

            return "<img src='data:{$mimeType};base64,{$base64}' alt='Bukti Foto' />";

        } catch (\Exception $e) {
            Log::error("Exception downloading image: {$e->getMessage()}", [
                'media_id' => $mediaItem->id,
                'url' => $url ?? 'unknown'
            ]);
            return $this->buildImageLinkFallback($mediaItem);
        }
    }

    /**
     * Get best image URL (prioritas conversion terkecil)
     */
    private function getBestImageUrl($mediaItem): string
    {
        // Prioritas: xls_thumb → thumb → original
        if ($mediaItem->hasGeneratedConversion('xls_thumb')) {
            return $mediaItem->getUrl('xls_thumb');
        }

        if ($mediaItem->hasGeneratedConversion('thumb')) {
            return $mediaItem->getUrl('thumb');
        }

        return $mediaItem->getUrl();
    }

    /**
     * Detect MIME type dari URL atau media item
     */
    private function detectMimeType(string $url, $mediaItem): string
    {
        // Cek dari extension URL
        if (str_contains($url, '.webp')) {
            return 'image/webp';
        }
        if (str_contains($url, '.png')) {
            return 'image/png';
        }
        if (str_contains($url, '.gif')) {
            return 'image/gif';
        }

        // Default ke JPEG (karena conversion biasanya JPG)
        return 'image/jpeg';
    }

    /**
     * Fallback: link ke gambar jika embed gagal
     */
    private function buildImageLinkFallback($mediaItem): string
    {
        $url = $this->getBestImageUrl($mediaItem);
        $fileName = e($mediaItem->file_name ?? 'Lihat Gambar');

        return "<a href='{$url}' target='_blank'>{$fileName}</a>";
    }

    /**
     * Build HTML footer
     */
    private function buildHtmlFooter(): string
    {
        return '</body></html>';
    }

    /**
     * Estimasi ukuran file export
     */
    public function estimateFileSize(Patrol $patrol, string $startDate, string $endDate): array
    {
        $this->loadPatrolRelations($patrol, $startDate, $endDate);

        $totalImages = 0;
        $totalTasks = 0;

        foreach ($patrol->users as $userPatrol) {
            if (!$userPatrol->user) continue;

            foreach ($userPatrol->user->patrolBatches as $batch) {
                foreach ($batch->userPatrolTasks as $task) {
                    $totalTasks++;
                    $imageCount = min($task->media->count(), $this->maxImagesPerTask);
                    $totalImages += $imageCount;
                }
            }
        }

        // Estimasi: HTML ~15KB + (gambar × 6KB avg per thumbnail)
        $estimatedSizeKB = 15 + ($totalImages * 6);
        $estimatedSizeMB = round($estimatedSizeKB / 1024, 2);

        return [
            'total_tasks' => $totalTasks,
            'total_images' => $totalImages,
            'estimated_size_kb' => $estimatedSizeKB,
            'estimated_size_mb' => $estimatedSizeMB,
        ];
    }
}
