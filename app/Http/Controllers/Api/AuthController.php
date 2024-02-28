<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Auth\LoginRequest;
use App\Jobs\Timeoff\ReevaluateTimeoffRegulationUserPeriod;
use App\Models\TimeoffRegulation;
use App\Models\User;
use App\Services\TimeoffRegulationService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseController
{
    public function login(LoginRequest $request)
    {
        // ReevaluateTimeoffRegulationUserPeriod::dispatch();
        $timeoffRegulation = TimeoffRegulation::findOrFail(1);
        TimeoffRegulationService::updateEndPeriod($timeoffRegulation);
        die;
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        return response()->json([
            'data' => ['token' => $user->createToken('default')->plainTextToken],
        ]);
    }
}
