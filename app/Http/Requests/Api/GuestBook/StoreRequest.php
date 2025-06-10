<?php

namespace App\Http\Requests\Api\GuestBook;

use App\Models\Branch;
use App\Rules\CompanyTenantedRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'branch_id' => ['required', new CompanyTenantedRule(Branch::class, 'Branch not found')],
            // 'is_check_out' => 'nullable|boolean',
            'name' => 'required|string|min:2|max:100',
            'address' => 'required|string|min:2|max:200',
            'location_destination' => 'required|string|min:2|max:200',
            'room' => 'required|string|min:2|max:200',
            'person_destination' => 'required|string|min:2|max:200',
            'vehicle_number' => 'nullable|string|min:2|max:50',
            'description' => 'required|string|min:2|max:200',
            'files' => 'required|array',
            'files.*' => 'required|mimes:' . config('app.image_mimes_types'),
        ];
    }
}
