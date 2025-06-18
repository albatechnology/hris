<?php

namespace App\Http\Requests\Api\UserPatrolBatch;

use Illuminate\Foundation\Http\FormRequest;

class SyncRequest extends FormRequest
{
    

    /**
     * Prepare inputs for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'user_id' => $this->user_id ?? auth('sanctum')->id(),
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
            'user_id' => ['required', 'exists:users,id'],
            // 'patrol_id' => ['required', new CompanyTenantedRule(Patrol::class, 'Patrol not found')],
            'patrol_id' => ['required', 'exists:patrols,id'],
            'datetime' => 'required|date_format:Y-m-d H:i:s',

            'tasks' => 'nullable|array',
            'tasks.*.datetime' => 'required|date_format:Y-m-d H:i:s',
            'tasks.*.description' => 'nullable|string',
            'tasks.*.location_id' => 'nullable',
            'tasks.*.patrol_task_id' => ['required'],
            'tasks.*.lat' => 'nullable|string',
            'tasks.*.lng' => 'nullable|string',
            'tasks.*.images' => 'nullable|array',

            'locations' => 'nullable|array',
            'locations.*.datetime' => 'required|date_format:Y-m-d H:i:s',
            'locations.*.lat' => 'nullable|string',
            'locations.*.lng' => 'nullable|string',
        ];
    }
}
// {
//     patrol_id: 42, required
//     datetime: "2025-03-26 21:20:00", required
//     tasks: [
//         {
//             datetime: "2025-03-26 21:20:00", required
//             description: string, optional
//             patrol_task_id: 2, required
//             lat: string, optional(tapi kalo bisa harus diisi, kalo kosong nanti ga ada coordinate nya)
//             lng: string, optional(tapi kalo bisa harus diisi, kalo kosong nanti ga ada coordinate nya)
//             images: [
//                  File,
//             ]
//         },
//         {
//             datetime: "2025-03-26 23:20:00", required
//             description: string, optional
//             patrol_task_id: 4, required
//             lat: string, optional(tapi kalo bisa harus diisi, kalo kosong nanti ga ada coordinate nya)
//             lng: string, optional(tapi kalo bisa harus diisi, kalo kosong nanti ga ada coordinate nya)
//             images: [
//                  File,
//                  File,
//             ]
//         }
//     ],
//     locations: [
//         {
//             datetime: "2025-03-26 23:20:00", required
//             lat: string, optional(tapi kalo bisa harus diisi, kalo kosong nanti ga ada coordinate nya)
//             lng: string, optional(tapi kalo bisa harus diisi, kalo kosong nanti ga ada coordinate nya)
//         },
//         {
//             datetime: "2025-03-26 23:20:00", required
//             lat: string, optional(tapi kalo bisa harus diisi, kalo kosong nanti ga ada coordinate nya)
//             lng: string, optional(tapi kalo bisa harus diisi, kalo kosong nanti ga ada coordinate nya)
//         },
//         {
//             datetime: "2025-03-26 23:20:00", required
//             lat: string, optional(tapi kalo bisa harus diisi, kalo kosong nanti ga ada coordinate nya)
//             lng: string, optional(tapi kalo bisa harus diisi, kalo kosong nanti ga ada coordinate nya)
//         },
//         {
//             datetime: "2025-03-26 23:20:00", required
//             lat: string, optional(tapi kalo bisa harus diisi, kalo kosong nanti ga ada coordinate nya)
//             lng: string, optional(tapi kalo bisa harus diisi, kalo kosong nanti ga ada coordinate nya)
//         }
//     ],
// }