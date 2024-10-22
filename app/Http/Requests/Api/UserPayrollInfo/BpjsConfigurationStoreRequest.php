<?php

namespace App\Http\Requests\Api\UserPayrollInfo;

use App\Enums\BpjsKesehatanCost;
use App\Enums\JaminanPensiunCost;
use App\Enums\JhtCost;
use App\Enums\NppBpjsKetenagakerjaan;
use App\Enums\PaidBy;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BpjsConfigurationStoreRequest extends FormRequest
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
            'upah_bpjs_kesehatan' => 'required|numeric',
            'upah_bpjs_ketenagakerjaan' => 'required|numeric',
            'bpjs_ketenagakerjaan_no' => 'required|string',
            'bpjs_ketenagakerjaan_date' => 'required|date_format:Y-m-d',
            'bpjs_kesehatan_no' => 'required|string',
            'bpjs_kesehatan_family_no' => 'nullable|string',
            'bpjs_kesehatan_date' => 'required|date_format:Y-m-d',
            'bpjs_kesehatan_cost' => ['required', Rule::enum(PaidBy::class)],
            'jht_cost' => ['required', Rule::enum(PaidBy::class)],
            'jaminan_pensiun_cost' => ['required', Rule::enum(PaidBy::class)],
            'jaminan_pensiun_date' => 'required|date_format:Y-m-d',
            'npp_bpjs_ketenagakerjaan' => ['required', Rule::enum(NppBpjsKetenagakerjaan::class)],
        ];
        // return [
        //     'bpjs_ketenagakerjaan_no' => 'nullable|string',
        //     'npp_bpjs_ketenagakerjaan' => ['required', Rule::enum(NppBpjsKetenagakerjaan::class)],
        //     'bpjs_ketenagakerjaan_date' => 'required|date_format:Y-m-d',
        //     'bpjs_kesehatan_no' => 'nullable|string',
        //     'bpjs_kesehatan_family_no' => 'nullable|string',
        //     'bpjs_kesehatan_date' => 'required|date_format:Y-m-d',
        //     'bpjs_kesehatan_cost' => ['required', Rule::enum(BpjsKesehatanCost::class)],
        //     'jht_cost' => ['required', Rule::enum(JhtCost::class)],
        //     'jaminan_pensiun_cost' => ['required', Rule::enum(JaminanPensiunCost::class)],
        //     'jaminan_pensiun_date' => 'required|date_format:Y-m-d',

        // ];
    }
}
