<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use phpseclib\Crypt\RSA;

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

        $this->middleware('auth:user', ['except' => ['login', 'register', 'getServerTimestamp']]);
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
                return $this->successLoginResponse($json_user, $token);
            } else {
                throw new AuthorizationException('Your email or password is wrong');
            }
        } catch (\Exception $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function register(Request $request)
    {
        try {
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

            return $this->createdResponse(null, 'Registration is successful, you can login using your new account');
        } catch (\Exception $exception) {
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
}
