<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\SendOtp;
use App\Models\Otp;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    use SendsPasswordResetEmails;

    public function sendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        try {
            $user = User::where('email', $request->email)->first(['id']);

            $otp = Otp::create([
                'user_id' => $user->id
            ]);

            $user->sendOtp();

            Mail::to($user)->send(new SendOtp($user, $otp->code));
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode());
        }

        return response()->json(['message' => 'OTP sent successfully']);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|digits:6'
        ]);

        $otp = Otp::where([
            'code' => $request->otp
        ])->whereActive()->first();

        if ($otp) return response()->json(['message' => 'OTP verified successfully']);

        Otp::where('code', $request->otp)->delete();
        return response()->json(['message' => 'OTP is invalid or expired'], 404);
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'otp' => 'required|string|digits:6',
            'password' => 'required|string|min:6',
        ]);

        $otp = Otp::where([
            'code' => $request->otp
        ])->whereActive()->first();

        try {
            if ($otp) {
                DB::transaction(function () use ($request, $otp) {
                    $user = $otp->user;
                    $user->password = bcrypt($request->password);
                    if (!$otp->user?->email_verified_at) {
                        $user->email_verified_at = now();
                    }
                    $user->save();

                    $otp->delete();
                });

                return response()->json(['message' => 'OTP verified successfully']);
            }

            Otp::where('code', $request->otp)->delete();
            return response()->json(['message' => 'OTP is invalid or expired'], 404);
        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }
}
