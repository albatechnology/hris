<?php

namespace App\Http\Requests\Api\Attendance;

use App\Enums\AttendanceType;
use App\Models\Shift;
use App\Models\User;
use App\Rules\CompanyTenantedRule;
use App\Services\UserService;
use App\Traits\Requests\RequestToBoolean;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManualAttendanceRequest extends FormRequest
{
    use RequestToBoolean;

    

    protected function prepareForValidation()
    {
        $type = $this->type ?? AttendanceType::MANUAL->value;

        $user = auth('sanctum')->user();
        if (!$user->is_user) {
            $type = AttendanceType::AUTOMATIC->value;
        } elseif (($user->id != $this->user_id) && UserService::isMyDescendant($user, $this->user_id)) {
            $type = AttendanceType::AUTOMATIC->value;
        }

        $isOfflineMode = $this->toBoolean($this->is_offline_mode ?? 0);
        if ($isOfflineMode) {
            $type = AttendanceType::MANUAL->value;
        }

        $this->merge([
            'type' => $type,
            'is_offline_mode' => $isOfflineMode,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', new CompanyTenantedRule(User::class, 'User not found')],
            'date' => 'required|date',
            'shift_id' => ['required_if:is_offline_mode,false', new CompanyTenantedRule(Shift::class, 'Shift not found', fn($q) => $q->orWhereNull('company_id'), fn($q) => $q->withTrashed())],
            'clock_in' => 'nullable|date_format:H:i',
            'clock_out' => 'nullable|date_format:H:i',
            'type' => ['required', Rule::enum(AttendanceType::class)],
            'is_offline_mode' => 'required|boolean',
            'lat' => 'nullable|string',
            'lng' => 'nullable|string',
        ];
    }
}
