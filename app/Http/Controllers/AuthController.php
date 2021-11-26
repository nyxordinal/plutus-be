<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:user', ['except' => ['login', 'register']]);
    }

    public function login(Request $request)
    {
        try {
            $this->validate($request, [
                'email' => 'required|email',
                'password' => 'required',
            ]);

            $token = auth('user')->claims(['role' => 'user'])->attempt($request->only('email', 'password'));
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
