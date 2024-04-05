<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:user');
    }

    public function getSummaryStat(Request $request)
    {
        try {
            Log::info("fetching summary stat");
            $user = User::find(auth('user')->user()->id);
            $expenseTotal = $user->expenses()
                ->select(
                    DB::raw('SUM(price) as total'),
                    DB::raw('COUNT(DISTINCT DATE_FORMAT(date, \'%Y-%m\')) as month_count')
                )
                ->first();
            $incomeTotal = $user->incomes()
                ->select(
                    DB::raw('SUM(amount) as total'),
                    DB::raw('COUNT(DISTINCT DATE_FORMAT(date, \'%Y-%m\')) as month_count')
                )
                ->first();
            Log::info("fetch summary stat success");
            return $this->successResponse([
                "expense" => [
                    "total" => $expenseTotal->total ? (int) $expenseTotal->total : 0,
                    "avg" => $expenseTotal->total / ($expenseTotal->month_count ? $expenseTotal->month_count : 1),
                ],
                "income" => [
                    "total" => $incomeTotal->total ? (int) $incomeTotal->total : 0,
                    "avg" => $incomeTotal->total / ($incomeTotal->month_count ? $incomeTotal->month_count : 1),
                ]
            ]);
        } catch (\Exception $exception) {
            Log::error("fetch summary stat failed", ['exception' => $exception]);
            return $this->errorResponse($exception);
        }
    }
}
