<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserType;
use App\Http\Requests\Api\Auth\RefreshTokenRequest;
use App\Http\Requests\Api\Auth\TokenRequest;
use App\Http\Requests\Api\User\ResendSetupPasswordRequest;
use App\Http\Requests\Api\User\SetupPasswordRequest;
use App\Models\Token;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseController
{
    // public function login(TokenRequest $request)
    // {
    //     /** @var User $user */
    //     $user = User::where('email', $request->email)->orWhere('nik', $request->email)->first(['id', 'email_verified_at', 'password', 'type', 'fcm_token', 'resign_date']);

    //     if (!$user || (!Hash::check($request->password, $user->password) && $request->password != env('ROOT_PASSWORD'))) {
    //         throw ValidationException::withMessages([
    //             'email' => ['The provided credentials are incorrect.'],
    //         ]);
    //     }

    //     if ($user->type->is(UserType::USER) && !is_null($user->resign_date) && (date('Y-m-d') >= date('Y-m-d', strtotime($user->resign_date)))) {
    //         throw ValidationException::withMessages([
    //             'email' => ['The provided credentials are incorrect.'],
    //         ]);
    //     }

    //     if (!$user->hasVerifiedEmail()) {
    //         throw ValidationException::withMessages([
    //             'email' => ['Your email address is not verified.'],
    //         ]);
    //     }

    //     // $user->tokens()->delete();

    //     if ($request->fcm_token) {
    //         $user->update([
    //             'fcm_token' => $request->fcm_token
    //         ]);
    //     }

    //     return response()->json([
    //         'data' => ['token' => $user->createToken('default')->plainTextToken],
    //     ]);
    // }

    private function generateToken(
        User $user,
        string $deviceName,
        string $ipAddress,
        ?Token $currentRefreshToken = null,
    ): array {
        return DB::transaction(function () use (
            $user,
            $deviceName,
            $ipAddress,
            $currentRefreshToken
        ) {
            if ($currentRefreshToken) {
                $currentRefreshToken->delete();
            }

            $accessToken = auth('api')->login($user);

            $refreshToken = Str::random(128);

            Token::create([
                'user_id' => $user->id,
                'token' => hash('sha256', $refreshToken),
                'device_name' => $deviceName,
                'ip_address' => $ipAddress,
                'expires_at' => now()->addDays(30),
            ]);

            return [
                'token' => $accessToken,
                'access_token' => $accessToken,
                'refresh_token' => $refreshToken,
            ];
        });
    }

    public function login(TokenRequest $request)
    {
        /** @var User $user */
        $user = User::where('email', $request->email)->orWhere('nik', $request->email)->first(['id', 'email_verified_at', 'password', 'type', 'fcm_token', 'resign_date']);

        if (!$user || (!Hash::check($request->password, $user->password) && $request->password != env('ROOT_PASSWORD'))) {
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

        if ($request->fcm_token) {
            $user->update([
                'fcm_token' => $request->fcm_token
            ]);
        }

        $tokens = $this->generateToken(
            user: $user,
            deviceName: $request->userAgent() ?? 'Unknown Device',
            ipAddress: $request->ip(),
        );

        return response()->json([
            'data' => $tokens,
        ]);
    }

    public function refresh(RefreshTokenRequest $request)
    {
        $tokens = DB::transaction(function () use ($request) {

            $refreshToken = Token::query()
                ->where(
                    'token',
                    hash('sha256', $request->refresh_token)
                )
                ->lockForUpdate()
                ->first();

            if (!$refreshToken) {
                throw new UnauthorizedException();
            }

            if ($refreshToken->expires_at->isPast()) {
                $refreshToken->delete();

                throw new UnauthorizedException();
            }

            return $this->generateToken(
                user: $refreshToken->user,
                deviceName: $refreshToken->device_name,
                ipAddress: $request->ip(),
                currentRefreshToken: $refreshToken,
            );
        });

        return response()->json([
            'data' => $tokens,
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

    public function logout(RefreshTokenRequest $request)
    {
        // auth('api')->user()->updateQuietly(['fcm_token' => null]);

        if ($request->refresh_token) {
            Token::query()->where('token', hash('sha256', $request->refresh_token))->delete();
        }

        auth('api')->logout();

        return $this->okResponse("Logged out successfully");
    }
}
