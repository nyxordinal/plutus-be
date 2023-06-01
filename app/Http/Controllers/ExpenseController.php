<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseType;
use App\Exceptions\ExpenseTypeException;
use App\Mail\ExpenseLimitAlert;
use App\Models\Expense;
use App\Models\User;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

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
            return $this->errorResponse($exception);
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
            return $this->errorResponse($exception);
        }
    }

    public function createExpense(Request $request)
    {
        try {
            $this->validate($request, [
                'name' => 'required|string|max:100',
                'type' => 'required|numeric',
                'price' => 'required|numeric|gt:0',
                'date' => 'required|date',
            ]);


            // validate expense type
            $type =  ExpenseType::coerce($request->type);
            if (!$type) {
                throw new ExpenseTypeException('expense type invalid');
            }

            // Create new expense
            $user = User::find(auth('user')->user()->id);
            $expense = $user->expenses()->create([
                'name' => $request->name,
                'type' => $type->value,
                'price' => $request->price,
                'date' => $request->date,
            ]);

            // check expense limit
            if ($user->expense_limit > 0) {
                $firstDateOfCurrentMonth = DateTime::createFromFormat('m-d-Y', date('m-01-Y'));
                $firstDateOfCurrentMonth = $firstDateOfCurrentMonth->sub(new DateInterval('P1D'));
                $lastDateOfCurrentMonth = DateTime::createFromFormat('m-d-Y', date('m-t-Y'));
                $lastDateOfCurrentMonth = $lastDateOfCurrentMonth->add(new DateInterval('P1D'));
                $totalExpense = Expense::where('user_id', $user->id)
                    ->whereBetween(
                        'date',
                        [$firstDateOfCurrentMonth, $lastDateOfCurrentMonth]
                    )->sum('price');
                if ($totalExpense >= $user->expense_limit) {
                    // send email
                    Mail::to($user)->send(new ExpenseLimitAlert($user));
                }
            }

            return $this->createdResponse($expense, 'Expense created');
        } catch (\Exception $exception) {
            return $this->errorResponse($exception);
        }
    }

    public function updateExpense(Request $request)
    {
        try {
            $this->validate($request, [
                'id' => 'required|exists:expenses,id',
                'name' => 'string|max:100',
                'type' => 'numeric',
                'price' => 'numeric|gt:0',
                'date' => 'date',
            ]);

            // validate expense type
            if ($request->type) {
                $type = ExpenseType::coerce($request->type);
                if (!$type) {
                    throw new ExpenseTypeException('expense type invalid');
                }
            }

            // update expense
            $expense = Expense::find($request->id);
            $expense->name = $request->name ? $request->name : $expense->name;
            $expense->type = $request->type ? $request->type : $expense->type;
            $expense->price = $request->price ? $request->price : $expense->price;
            $expense->date = $request->date ? $request->date : $expense->date;
            $expense->save();

            return $this->successResponse($expense, 'Expense updated');
        } catch (\Exception $exception) {
            return $this->errorResponse($exception);
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
        } catch (\Exception $exception) {
            return $this->errorResponse($exception);
        }
    }
}
