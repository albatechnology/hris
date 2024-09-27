<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Auth\LoginRequest;
use App\Http\Requests\Api\User\ResendSetupPasswordRequest;
use App\Http\Requests\Api\User\SetupPasswordRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseController
{
    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->hasVerifiedEmail()){
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

            $notificationType = \App\Enums\NotificationType::SETUP_PASSWORD;
            $user->notify(new ($notificationType->getNotificationClass())($notificationType));
            
            return response()->json('success', 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
    
    public function setupPassword(SetupPasswordRequest $request)
    {
        try {
            $decryptedEmail = openssl_decrypt(base64_decode(urldecode($request->token)), 'AES-128-CBC', env('CRYPT_SECRET_KEY'), OPENSSL_RAW_DATA, env('CRYPT_IV'));
            if (!$decryptedEmail) {
                return $this->errorResponse('Invalid token');
            }

            $user = User::where('email', $decryptedEmail)->first();

            if ($user->hasVerifiedEmail()){
                throw ValidationException::withMessages([
                    'email' => ['Your email is already verified.'],
                ]);
            }

            $user->update([
                'email_verified_at' => now(),
                'password' => $request->password,
            ]);
            
            return response()->json('success', 200);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
