<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IncomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:user');
    }

    public function getIncomeSummary()
    {
        try {
            $user = User::find(auth('user')->user()->id);
            $incomeSummary = $user->incomes()
                ->select(
                    DB::raw('DATE_FORMAT(date,\'%Y-%m\') as yearmonth'),
                    DB::raw('SUM(amount) as amount')
                )
                ->groupBy('yearmonth')
                ->orderBy('yearmonth', 'desc')
                ->get();
            $total = $incomeSummary->sum('amount');
            return $this->successResponse([
                "total" => $total,
                "avg" => $total / $incomeSummary->count(),
                "data" => $incomeSummary
            ]);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function getIncome(Request $request)
    {
        try {
            $dataPerPage = $request->query('count', '5');
            $user = User::find(auth('user')->user()->id);
            $incomes = $user->incomes()->orderBy('date', 'desc')
                ->paginate($dataPerPage);
            return $this->successResponse($incomes);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function createIncome(Request $request)
    {
        try {
            $this->validate($request, [
                'source' => 'required|string|max:100',
                'amount' => 'required|numeric|gt:0|max:10000000000',
                'date' => 'required|date',
            ]);

            // Create new income
            $user = User::find(auth('user')->user()->id);
            $income = $user->incomes()->create([
                'source' => $request->source,
                'amount' => $request->amount,
                'date' => $request->date,
            ]);

            return $this->createdResponse($income, 'Income created');
        } catch (\Exception $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function updateIncome(Request $request)
    {
        try {
            $this->validate($request, [
                'id' => 'required|exists:incomes,id',
                'source' => 'string|max:100',
                'amount' => 'numeric|gt:0',
                'date' => 'date',
            ]);

            // update income
            $expense = Income::find($request->id);
            $expense->source = $request->source ? $request->source : $expense->source;
            $expense->amount = $request->amount ? $request->amount : $expense->amount;
            $expense->date = $request->date ? $request->date : $expense->date;
            $expense->save();

            return $this->successResponse($expense, 'Income updated');
        } catch (\Exception $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function bulkDeleteIncome(Request $request)
    {
        try {
            $this->validate($request, [
                'ids' => 'required|array|min:1',
            ]);

            // bulk delete income
            Income::destroy($request->ids);

            return $this->successResponse(null, 'Incomes deleted');
        } catch (\Exception $exception) {
            return $this->errorResponse($exception);
        }
    }
}
