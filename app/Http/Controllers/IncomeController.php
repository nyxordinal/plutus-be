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

    public function getIncomeSummary(Request $request)
    {
        try {
            $dataPerPage = $request->query('count', '5');
            $user = User::find(auth('user')->user()->id);
            $incomeSummary = $user->incomes()
                ->select(
                    DB::raw('DATE_FORMAT(date,\'%Y-%m\') as yearmonth'),
                    DB::raw('SUM(amount) as amount')
                )
                ->groupBy('yearmonth')
                ->orderBy('yearmonth', 'desc')
                ->paginate($dataPerPage);
            return $this->successResponse($incomeSummary);
        } catch (\Exception $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function getIncome(Request $request)
    {
        try {
            $startDate = $request->query('start', '0001-01-01 00:00:00');
            $endDate = $request->query('end', '9999-12-31 23:59:59');
            $source = $request->query('source', '');
            $dataPerPage = $request->query('count', '5');
            $user = User::find(auth('user')->user()->id);
            $incomes = $user->incomes()->where([
                ['source', 'like', '%' . $source . '%'],
                ['date', '>=', $startDate],
                ['date', '<=', $endDate],
            ])
                ->orderBy('date', 'desc')
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
