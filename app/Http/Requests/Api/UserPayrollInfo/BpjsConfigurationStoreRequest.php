<?php

namespace App\Http\Requests\Api\UserPayrollInfo;

use App\Enums\JaminanPensiunCost;
use App\Enums\PaidBy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BpjsConfigurationStoreRequest extends FormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'upah_bpjs_kesehatan' => 'nullable|numeric',
            'upah_bpjs_ketenagakerjaan' => 'nullable|numeric',
            'bpjs_ketenagakerjaan_no' => 'nullable|string',
            'bpjs_ketenagakerjaan_date' => 'nullable|date_format:Y-m-d',
            'bpjs_kesehatan_no' => 'nullable|string',
            'bpjs_kesehatan_family_no' => 'nullable|string',
            'bpjs_kesehatan_date' => 'nullable|date_format:Y-m-d',
            'bpjs_kesehatan_cost' => ['nullable', Rule::enum(PaidBy::class)],
            'jht_cost' => ['nullable', Rule::enum(PaidBy::class)],
            'jaminan_pensiun_cost' => ['nullable', Rule::enum(JaminanPensiunCost::class)],
            'jaminan_pensiun_date' => 'nullable|date_format:Y-m-d',
            // 'npp_bpjs_ketenagakerjaan' => ['required', Rule::enum(NppBpjsKetenagakerjaan::class)],
        ];
        // return [
        //     'bpjs_ketenagakerjaan_no' => 'nullable|string',
        //     'npp_bpjs_ketenagakerjaan' => ['nullable', Rule::enum(NppBpjsKetenagakerjaan::class)],
        //     'bpjs_ketenagakerjaan_date' => 'nullable|date_format:Y-m-d',
        //     'bpjs_kesehatan_no' => 'nullable|string',
        //     'bpjs_kesehatan_family_no' => 'nullable|string',
        //     'bpjs_kesehatan_date' => 'nullable|date_format:Y-m-d',
        //     'bpjs_kesehatan_cost' => ['nullable', Rule::enum(BpjsKesehatanCost::class)],
        //     'jht_cost' => ['nullable', Rule::enum(JhtCost::class)],
        //     'jaminan_pensiun_cost' => ['nullable', Rule::enum(JaminanPensiunCost::class)],
        //     'jaminan_pensiun_date' => 'nullable|date_format:Y-m-d',

        // ];
    }
}
