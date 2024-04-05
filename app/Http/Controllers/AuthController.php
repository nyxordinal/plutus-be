<?php

namespace App\Http\Controllers;

use App\Mail\PasswordReset;
use App\Mail\RegistrationNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use phpseclib\Crypt\RSA;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    const TIMESTAMP_DURATION = 20; // seconds
    const ENC_PASSWORD_DELIMITER = ':';

    private $rsa;

    public function __construct()
    {
        // create rsa encrypt and load private key
        $this->rsa = new RSA();
        $key = str_replace('\n', "\n", env('RSA_PRIVATE_KEY'));
        $this->rsa->loadKey($key);

        $this->middleware('auth:user', ['except' => ['login', 'register', 'getServerTimestamp', 'sendResetPasswordEmail', 'resetPassword']]);
    }

    private function decodeRSA(string $cipher_text)
    {
        return explode(self::ENC_PASSWORD_DELIMITER, $this->rsa->decrypt(base64_decode($cipher_text)));
    }

    private function validateTimestamp(string $timestampStr)
    {
        $currentTimestamp = Carbon::now('UTC')->getTimestamp();
        $timestamp = Carbon::createFromTimestampUTC($timestampStr)->addSeconds(self::TIMESTAMP_DURATION)->getTimestamp();

        return $currentTimestamp < $timestamp ? true : false;
    }

    public function getServerTimestamp()
    {
        return $this->successLoginResponse(Carbon::now('UTC')->getTimestamp());
    }

    public function login(Request $request)
    {
        try {
            Log::info("start login account", ['req_body' => request()->all()]);
            $this->validate($request, [
                'email' => 'required|email',
                'enc_password' => 'required',
            ]);

            // decrypt enc password
            $encData = self::decodeRSA($request->enc_password);
            $password = $encData[0];
            $timestamp = $encData[1];

            // validate timestamp
            if (!self::validateTimestamp($timestamp)) {
                throw new AuthorizationException('Your email or password is wrong');
            }
            $token = auth('user')->claims(['role' => 'user'])->attempt([
                'email' => $request->email,
                'password' => $password,
            ]);
            if ($token) {
                $user = User::where('email', $request->email)->first();
                $json_user = $user->toArray();
                Log::info("login success");
                return $this->successLoginResponse($json_user, $token);
            } else {
                Log::info("login failed because email or password is wrong");
                throw new AuthorizationException('Your email or password is wrong');
            }
        } catch (\Exception $exception) {
            Log::error("account login failed", ['exception' => $exception]);
            return $this->errorResponse($exception);
        }
    }

    public function register(Request $request)
    {
        try {
            Log::info("registering new account", ['req_body' => request()->all()]);
            $this->validate($request, [
                'name' => 'required|max:100',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
            ]);

            // Create new user data
            $user = new User;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);
            $user->save();

            // send email to admin
            Log::info("sending new account registration email notif to admin");
            Mail::to($user)->send(new RegistrationNotification($user));

            Log::info("register new account success");
            return $this->createdResponse(null, 'Registration is successful, you can login using your new account');
        } catch (\Exception $exception) {
            Log::error("account register failed", ['exception' => $exception]);
            return $this->errorResponse($exception);
        }
    }

    public function me()
    {
        try {
            return $this->successResponse(auth('user')->user());
        } catch (\Exception $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function refresh()
    {
        try {
            return $this->successResponse(["token" => auth('user')->refresh()]);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function sendResetPasswordEmail(Request $request)
    {
        try {
            Log::info("start send reset password email", ['req_body' => request()->all()]);
            $this->validate($request, [
                'email' => 'required|email',
            ]);

            // check user data
            $user = User::where('email', $request->email)->first();
            if ($user) {
                // generate reset token
                $resetToken = Str::random(50);

                // store reset token
                $user->reset_token = $resetToken;
                $user->save();

                // send email
                Log::info("sending reset password email");
                Mail::to($user)->send(new PasswordReset($user));
            }

            Log::info("send reset password email success");
            return $this->successResponse(null, "Reset password email has been sent to your email");
        } catch (\Exception $exception) {
            Log::error("send reset password email failed", ['exception' => $exception]);
            return $this->errorResponse($exception);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            Log::info("resetting password for " . $request->email);
            $this->validate($request, [
                'email' => 'required|email',
                'token' => 'required',
                'enc_password' => 'required'
            ]);

            // get user data
            $user = User::where('email', $request->email)->firstOrFail();

            // decrypt enc password
            $encData = self::decodeRSA($request->enc_password);
            $password = $encData[0];
            $timestamp = $encData[1];

            // validate timestamp
            if (!self::validateTimestamp($timestamp)) {
                Log::info("resetting password failed because timestamp in request is invalid");
                throw new AuthorizationException('Request is invalid');
            }

            // validate reset token
            if (strcmp($request->token, $user->reset_token) != 0) {
                Log::info("resetting password failed because reset password token is invalid");
                throw new AuthorizationException('Token is invalid, please request a new reset password email');
            }

            $user->reset_token = '';
            $user->password = Hash::make($password);
            $user->save();

            return $this->successResponse(null, "Reset password is success, you can login with your new password");
        } catch (\Exception $exception) {
            Log::error("reset password failed", ['exception' => $exception]);
            return $this->errorResponse($exception);
        }
    }
}
