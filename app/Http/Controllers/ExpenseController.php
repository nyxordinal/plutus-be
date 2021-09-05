<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\User;
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
            $startDate = $request->query('start', '0001-01-01 00:00:00');
            $endDate = $request->query('end', '9999-12-31 23:59:59');
            $name = $request->query('name', '');
            $dataPerPage = $request->query('count', '5');
            $user = User::find(auth('user')->user()->id);
            $expenses = $user->expenses()->where([
                ['name', 'like', '%' . $name . '%'],
                ['date', '>=', $startDate],
                ['date', '<=', $endDate],
            ])
                ->orderBy('date', 'desc')
                ->paginate($dataPerPage);
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

    public function bulkDeleteExpense(Request $request)
    {
        try {
            $this->validate($request, [
                'ids' => 'required|array|min:1',
            ]);

            // bulk delete expense
            Expense::destroy($request->ids);

            return $this->successResponse(null, 'Expenses deleted');
        } catch (\Exception $e) {
            if ($e instanceof ValidationException) {
                return $this->badRequestResponse($e->errors());
            } else {
                return $this->internalServerErrorResponse($e);
            }
        }
    }
}
