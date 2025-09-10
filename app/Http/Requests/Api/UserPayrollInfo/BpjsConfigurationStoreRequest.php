<?php

namespace App\Http\Requests\Api\UserPayrollInfo;

use App\Enums\BpjsKesehatanFamilyNo;
use App\Enums\JaminanPensiunCost;
use App\Enums\PaidBy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BpjsConfigurationStoreRequest extends FormRequest
{
    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'upah_bpjs_ketenagakerjaan' => $this->bpjs_ketenagakerjaan_no ? $this->upah_bpjs_ketenagakerjaan : 0,
            'upah_bpjs_kesehatan' => $this->bpjs_kesehatan_no ? $this->upah_bpjs_kesehatan : 0,
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
            'bpjs_kesehatan_no' => ['nullable', 'string'],
            'upah_bpjs_kesehatan' => ['nullable', 'numeric', 'min:0'],
            'bpjs_kesehatan_date' => ['required_with:bpjs_kesehatan_no', 'date_format:Y-m-d'],

            'bpjs_ketenagakerjaan_no' => ['nullable', 'string'],
            'upah_bpjs_ketenagakerjaan' => ['nullable', 'numeric', 'min:0'],
            'bpjs_ketenagakerjaan_date' => ['required_with:bpjs_ketenagakerjaan_no', 'date_format:Y-m-d'],

            'bpjs_kesehatan_family_no' => ['required', Rule::enum(BpjsKesehatanFamilyNo::class)],
            'bpjs_kesehatan_cost' => ['nullable', Rule::enum(PaidBy::class)],
            'jht_cost' => ['nullable', Rule::enum(PaidBy::class)],
            'jaminan_pensiun_cost' => ['nullable', Rule::enum(JaminanPensiunCost::class)],
            'jaminan_pensiun_date' => ['nullable', 'date_format:Y-m-d'],
            // 'npp_bpjs_ketenagakerjaan' => ['required', Rule::enum(NppBpjsKetenagakerjaan::class)],
        ];
    }
}
