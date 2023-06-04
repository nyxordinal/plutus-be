<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:user');
    }

    public function getSettings()
    {
        try {
            $user = User::find(auth('user')->user()->id);
            return $this->successResponse(['settings' => [
                'expense_limit' => $user->expense_limit,
                'last_notif_date' => $user->last_notif_date
            ]]);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function updateSettings(Request $request)
    {
        try {
            $this->validate($request, [
                'expense_limit' => 'required|numeric|min:1',
                'is_reset_notif' => 'required|boolean',
            ]);
            $user = User::find(auth('user')->user()->id);
            $user->expense_limit = $request->expense_limit;
            if ($request->is_reset_notif) {
                $user->last_notif_date = Carbon::createFromDate(1970, 1, 1);
            }
            $user->save();
            return $this->successResponse(null, 'Settings updated');
        } catch (\Exception $exception) {
            return $this->errorResponse($exception);
        }
    }
}
