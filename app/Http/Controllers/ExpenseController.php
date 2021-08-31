<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:user');
    }

    public function getExpenseSummary()
    {
        try {
            $user = User::find(auth('user')->user()->id);
            $expenseSummary = $user->expenses()
                ->select(
                    DB::raw('DATE_FORMAT(date,\'%Y-%m\') as yearmonth'),
                    DB::raw('SUM(price) as amount')
                )
                ->groupBy('yearmonth')
                ->orderBy('yearmonth', 'desc')
                ->get();
            return $this->successResponse($expenseSummary);
        } catch (\Exception $exception) {
            return $this->internalServerErrorResponse($exception);
        }
    }

    public function getExpense(Request $request)
    {
        try {
            $start_date = $request->query('start', '0001-01-01 00:00:00');
            $end_date = $request->query('end', '9999-12-31 23:59:59');
            $user = User::find(auth('user')->user()->id);
            $expenses = $user->expenses()->where([
                ['date', '>=', $start_date],
                ['date', '<=', $end_date],
            ])->get();
            return $this->successResponse($expenses);
        } catch (\Exception $exception) {
            return $this->internalServerErrorResponse($exception);
        }
    }

    public function createExpense(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'required|string|max:100',
                'type_id' => 'required|exists:expense_types,id',
                'price' => 'required|numeric|gt:0',
                'date' => 'required|date',
            ]);

            // Create new expense
            $user = User::find(auth('user')->user()->id);
            $expense = $user->expenses()->create([
                'name' => $request->name,
                'type_id' => $request->type_id,
                'price' => $request->price,
                'date' => $request->date,
            ]);

            return $this->createdResponse($expense, 'Expense created');
        } catch (\Exception $exception) {
            if ($exception instanceof ValidationException) {
                return $this->badRequestResponse($exception->errors());
            } else {
                return $this->internalServerErrorResponse($exception);
            }
        }
    }

    public function updateExpense(Request $request)
    {
        try {
            $this->validate($request, [
                'id' => 'required|exists:expenses,id',
                'name' => 'string|max:100',
                'type_id' => 'exists:expense_types,id',
                'price' => 'numeric|gt:0',
                'date' => 'date',
            ]);

            // update expense
            $expense = Expense::find($request->id);
            $expense->name = $request->name ? $request->name : $expense->name;
            $expense->type_id = $request->type_id ? $request->type_id : $expense->type_id;
            $expense->price = $request->price ? $request->price : $expense->price;
            $expense->date = $request->date ? $request->date : $expense->date;
            $expense->save();

            return $this->successResponse($expense, 'Expense updated');
        } catch (\Exception $exception) {
            if ($exception instanceof ValidationException) {
                return $this->badRequestResponse($exception->errors());
            } else {
                return $this->internalServerErrorResponse($exception);
            }
        }
    }

    public function deleteExpense($id)
    {
        try {
            $expense = Expense::findOrFail($id);

            // delete expense
            $expense->delete();

            return $this->successResponse(null, 'Expense deleted');
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                return $this->badRequestResponse($e->errors());
            } else if ($e instanceof ModelNotFoundException) {
                return $this->badRequestResponse('expense not found');
            } else {
                return $this->internalServerErrorResponse($e);
            }
        }
    }
}
