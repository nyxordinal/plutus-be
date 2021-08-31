<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
            return $this->successResponse($incomeSummary);
        } catch (\Exception $exception) {
            return $this->internalServerErrorResponse($exception);
        }
    }

    public function getIncome()
    {
        try {
            $user = auth('user')->user();
            return $this->successResponse($user->incomes);
        } catch (\Exception $exception) {
            return $this->internalServerErrorResponse($exception);
        }
    }

    public function createIncome(Request $request)
    {
        try {
            $this->validate($request, [
                'source' => 'required|string|max:100',
                'amount' => 'required|numeric|gt:0',
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
            if ($exception instanceof ValidationException) {
                return $this->badRequestResponse($exception->errors());
            } else {
                return $this->internalServerErrorResponse($exception);
            }
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
            if ($exception instanceof ValidationException) {
                return $this->badRequestResponse($exception->errors());
            } else {
                return $this->internalServerErrorResponse($exception);
            }
        }
    }

    public function deleteIncome($id)
    {
        try {
            $income = Income::findOrFail($id);

            // delete income
            $income->delete();

            return $this->successResponse(null, 'Income deleted');
        } catch (\Exception $exception) {
            if ($exception instanceof ValidationException) {
                return $this->badRequestResponse($exception->errors());
            } else if ($exception instanceof ModelNotFoundException) {
                return $this->badRequestResponse('income not found');
            } else {
                return $this->internalServerErrorResponse($exception);
            }
        }
    }
}
