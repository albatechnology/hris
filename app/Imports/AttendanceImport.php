<?php
// filepath: app/Imports/AttendanceImport.php

namespace App\Imports;

use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceDetail;
use App\Services\ScheduleService;
use App\Enums\AttendanceType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Validators\Failure;
use Carbon\Carbon;

class AttendanceImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithValidation,
    SkipsOnError,
    SkipsOnFailure
{
    /**
     * Store import statistics
     */
    protected array $stats = [
        'total' => 0,
        'created' => 0,
        'updated' => 0,
        'skipped' => 0,
        'errors' => [],
    ];

    /**
     * Cache users by NIK for performance
     */
    protected Collection $usersByNik;

    /**
     * Constructor
     */
    public function __construct()
    {
        // Pre-load all users dengan NIK untuk menghindari N+1 query
        // Gunakan tenanted() jika ada multi-tenancy
        $this->usersByNik = User::tenanted()
            ->whereNotNull('nik')
            ->get(['id', 'nik', 'company_id'])
            ->keyBy('nik');
        // dd($this->usersByNik);
    }

    /**
     * Process imported collection
     *
     * @param Collection $rows
     * @return void
     */
    public function collection(Collection $rows)
    {
        // Gunakan transaction untuk memastikan data consistency
        DB::transaction(function () use ($rows) {
            foreach ($rows as $index => $row) {
                $this->stats['total']++;

                try {
                    $this->processRow($row, $index);
                } catch (\Exception $e) {
                    $this->stats['skipped']++;
                    $this->stats['errors'][] = [
                        'row' => $index + 2, // +2 karena header di row 1, index dimulai dari 0
                        'nik' => $row['nik'] ?? 'N/A',
                        'date' => $row['date'] ?? 'N/A',
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Attendance Import Error', [
                        'row' => $index + 2,
                        'data' => $row->toArray(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        });
    }

    /**
     * Process single row
     *
     * @param Collection $row
     * @param int $index
     * @return void
     */
    protected function processRow(Collection $row, int $index): void
    {
        // ═══════════════════════════════════════════════════════════
        // STEP 1: Validate & Extract Data
        // ═══════════════════════════════════════════════════════════
        $nik = trim($row['nik'] ?? '');
        $date = $this->parseDate($row['date'] ?? '');
        $checkIn = $this->parseTime($row['check_in'] ?? '');
        $checkOut = $this->parseTime($row['check_out'] ?? '');
        $lat = $row['clock_in_coordinate'] ?? '';
        $lng = $row['clock_out_coordinate'] ?? '';

        // Skip jika NIK atau tanggal kosong
        if (empty($nik) || empty($date)) {
            $this->stats['skipped']++;
            $this->stats['errors'][] = [
                'row' => $index + 2,
                'nik' => $nik,
                'date' => $date,
                'error' => 'NIK atau Tanggal kosong',
            ];
            return;
        }

        // Skip jika kedua jam kosong
        if (empty($checkIn) && empty($checkOut)) {
            $this->stats['skipped']++;
            $this->stats['errors'][] = [
                'row' => $index + 2,
                'nik' => $nik,
                'date' => $date,
                'error' => 'Check In dan Check Out kosong',
            ];
            return;
        }

        // ═══════════════════════════════════════════════════════════
        // STEP 2: Find User by NIK
        // ═══════════════════════════════════════════════════════════
        $user = $this->usersByNik->get($nik);
        // dd($user);

        if (!$user) {
            $this->stats['skipped']++;
            $this->stats['errors'][] = [
                'row' => $index + 2,
                'nik' => $nik,
                'date' => $date,
                'error' => "User dengan NIK {$nik} tidak ditemukan",
            ];
            return;
        }

        // ═══════════════════════════════════════════════════════════
        // STEP 3: Resolve schedule/shift for the date (if available)
        // ═══════════════════════════════════════════════════════════
        $schedule = ScheduleService::getTodaySchedule($user, $date, ['id'], ['id']);
        $scheduleId = $schedule?->id;
        $shiftId = $schedule?->shift?->id;
        

        // ═══════════════════════════════════════════════════════════
        // STEP 4: Find or Create Attendance (idempotent per user+date)
        // ═══════════════════════════════════════════════════════════
        $attendance = Attendance::firstOrCreate(
            [
                'user_id' => $user->id,
                'date' => $date,
            ],
            [
                'user_id' => $user->id,
                'date' => $date,
                'schedule_id' => $scheduleId,
                'shift_id' => $shiftId,
                
            ]
        );

        $wasRecentlyCreated = $attendance->wasRecentlyCreated; //fungsi eloquent laravel
        // Backfill schedule/shift if attendance existed without them
        if (!$wasRecentlyCreated) {
            $dirty = false; //flag
            if (is_null($attendance->schedule_id) && $scheduleId) {
                $attendance->schedule_id = $scheduleId; $dirty = true;
            }
            if (is_null($attendance->shift_id) && $shiftId) {
                $attendance->shift_id = $shiftId; $dirty = true;
            }
            if ($dirty) {
                $attendance->save();
            }
        }

        // ═══════════════════════════════════════════════════════════
        // STEP 5: Update or Create Attendance Details
        // ═══════════════════════════════════════════════════════════

        // Handle Check In (is_clock_in = true)
        if (!empty($checkIn)) {
            AttendanceDetail::updateOrCreate(
                [
                    'attendance_id' => $attendance->id,
                    'is_clock_in' => true,
                ],
                [
                    'attendance_id' => $attendance->id,
                    'is_clock_in' => true,
                    'time' => $date . ' ' . $checkIn,
                    'type' => AttendanceType::AUTOMATIC->value,
                    'note' => 'Imported',
                    'lat'=> $lat,
                    'lng'=> $lng,
                ]
            );
        }

        // Handle Check Out (is_clock_in = false)
        if (!empty($checkOut)) {
            AttendanceDetail::updateOrCreate(
                [
                    'attendance_id' => $attendance->id,
                    'is_clock_in' => false,
                ],
                [
                    'attendance_id' => $attendance->id,
                    'is_clock_in' => false,
                    'time' => $date . ' ' . $checkOut,
                    'type' => AttendanceType::AUTOMATIC->value,
                    'note' => 'Imported',
                    'lat'=> $lat,
                    'lng'=> $lng,
                ]
            );
        }

        // ═══════════════════════════════════════════════════════════
        // STEP 6: Update Statistics
        // ═══════════════════════════════════════════════════════════
        if ($wasRecentlyCreated) {
            $this->stats['created']++;
        } else {
            $this->stats['updated']++;
        }
    }

    /**
     * Parse date from Excel
     * Support multiple formats: Y-m-d, d/m/Y, d-m-Y
     *
     * @param mixed $value
     * @return string|null
     */
    protected function parseDate($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Handle Excel date serial number (e.g., 44927 = 2023-01-01)
            if (is_numeric($value)) {
                return Carbon::createFromFormat('Y-m-d', '1899-12-30')
                    ->addDays($value)
                    ->format('Y-m-d');
            }

            // Try multiple date formats
            $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d'];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $value)->format('Y-m-d');
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Fallback: use Carbon's flexible parsing
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            throw new \Exception("Format tanggal tidak valid: {$value}");
        }
    }

    /**
     * Parse time from Excel
     * Support formats: H:i:s, H:i
     *
     * @param mixed $value
     * @return string|null
     */
    protected function parseTime($value): ?string
    {
        if (empty($value)) {
            return null;
        }

        try {
            // Handle Excel time serial number (e.g., 0.5 = 12:00:00)
            if (is_numeric($value) && $value <= 1) {
                $seconds = $value * 86400; // 24 hours * 60 minutes * 60 seconds
                $hours = floor($seconds / 3600);
                $minutes = floor(($seconds % 3600) / 60);
                $seconds = $seconds % 60;
                return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
            }

            // Try time formats
            $formats = ['H:i:s', 'H:i', 'h:i A', 'h:i:s A'];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $value)->format('H:i:s');
                } catch (\Exception $e) {
                    continue;
                }
            }

            // Fallback: use Carbon's flexible parsing
            return Carbon::parse($value)->format('H:i:s');
        } catch (\Exception $e) {
            throw new \Exception("Format waktu tidak valid: {$value}");
        }
    }

    /**
     * Validation rules for each row
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'nik' => ['required'],
            'date' => ['required'],
            // Check in dan check out optional, minimal salah satu harus ada
        ];
    }

    /**
     * Custom validation messages
     *
     * @return array
     */
    public function customValidationMessages(): array
    {
        return [
            'nik.required' => 'NIK tidak boleh kosong',
            'date.required' => 'Tanggal tidak boleh kosong',
        ];
    }

    /**
     * Handle validation errors
     *
     * @param Failure ...$failures
     * @return void
     */
    public function onFailure(Failure ...$failures)
    {
        foreach ($failures as $failure) {
            $this->stats['skipped']++;
            $this->stats['errors'][] = [
                'row' => $failure->row(),
                'attribute' => $failure->attribute(),
                'errors' => $failure->errors(),
                'values' => $failure->values(),
            ];
        }
    }

    /**
     * Handle general errors
     *
     * @param \Throwable $e
     * @return void
     */
    public function onError(\Throwable $e)
    {
        Log::error('Attendance Import Fatal Error', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }

    /**
     * Set chunk size for reading large files
     *
     * @return int
     */
    public function chunkSize(): int
    {
        return 500; // Process 500 rows per chunk
    }

    /**
     * Get import statistics
     *
     * @return array
     */
    public function getStats(): array
    {
        return $this->stats;
    }
}
