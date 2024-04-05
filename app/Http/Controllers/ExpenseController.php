<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseType;
use App\Enums\MailType;
use App\Exceptions\ExpenseTypeException;
use App\Jobs\SendEmailJob;
use App\Models\Expense;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpenseController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:user');
    }

    public function getExpenseSummary(Request $request)
    {
        try {
            Log::info("fetching expense summary", ["req_query" => request()->query()]);
            $dataPerPage = $request->query('count', '5');
            $user = User::find(auth('user')->user()->id);
            $expensesSummary = $user->expenses()
                ->select(
                    DB::raw('DATE_FORMAT(date,\'%Y-%m\') as yearmonth'),
                    DB::raw('SUM(price) as amount')
                )
                ->groupBy('yearmonth')
                ->orderBy('yearmonth', 'desc')
                ->paginate($dataPerPage);
            Log::info("fetch expense summary success");
            return $this->successResponse($expensesSummary);
        } catch (\Exception $exception) {
            Log::error("fetch expense summary failed", ['exception' => $exception]);
            return $this->errorResponse($exception);
        }
    }

    public function getExpense(Request $request)
    {
        try {
            Log::info("fetching expense", ["req_query" => request()->query()]);
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
            Log::info("fetch expense success");
            return $this->successResponse($expenses);
        } catch (\Exception $exception) {
            Log::error("fetch expense failed", ['exception' => $exception]);
            return $this->errorResponse($exception);
        }
    }

    public function createExpense(Request $request)
    {
        try {
            Log::info("creating expense", ["req_body" => request()->all()]);
            $this->validate($request, [
                'name' => 'required|string|max:100',
                'type' => 'required|numeric',
                'price' => 'required|numeric|gt:0|max:10000000000',
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

            $currentDateTime = Carbon::now();

            // check daily expense limit
            $this->checkDailyLimit($user, $currentDateTime);

            // check expense limit
            if ($user->expense_limit > 0 && $this->checkNotSameMonthAndYear($currentDateTime, $user->last_notif_date)) {
                $firstDateOfCurrentMonth = Carbon::now()->startOfMonth();
                $firstDateOfCurrentMonth = $firstDateOfCurrentMonth->subDay();
                $lastDateOfCurrentMonth = Carbon::now()->endOfMonth();
                $lastDateOfCurrentMonth = $lastDateOfCurrentMonth->addDay();
                $totalExpense = Expense::where('user_id', $user->id)
                    ->whereBetween(
                        'date',
                        [$firstDateOfCurrentMonth, $lastDateOfCurrentMonth]
                    )->sum('price');
                if ($totalExpense >= $user->expense_limit) {
                    // send email
                    Log::info("total expense has exceed user's expense limit, sending expense limit notif email");
                    dispatch(new SendEmailJob($user->email, $user, $totalExpense, MailType::ExpenseLimit()));
                    Log::info("job to send expense limit notif email is created");
                    $user->last_notif_date = $currentDateTime;
                    $user->save();
                }
            }

            Log::info("create expense success");
            return $this->createdResponse($expense, 'Expense created');
        } catch (\Exception $exception) {
            Log::error("create expense failed", ['exception' => $exception]);
            return $this->errorResponse($exception);
        }
    }

    private function checkDailyLimit($user, $currentDateTime)
    {
        $todayDate = $currentDateTime->format('Y-m-d');
        $totalDailyExpense = Expense::where('user_id', $user->id)
            ->whereBetween('date', [$todayDate, $todayDate])
            ->sum('price');
        if ($totalDailyExpense >= $user->expense_limit_daily) {
            Log::info("total daily expense has exceeded user's daily expense limit, sending daily expense limit notif email");
            dispatch(new SendEmailJob($user->email, $user, $totalDailyExpense, MailType::ExpenseDailyLimit()));
            Log::info("job to send daily expense limit notif email is created");
        }
    }

    private function checkNotSameMonthAndYear($date1, $date2)
    {
        $carbonDate1 = Carbon::parse($date1);
        $carbonDate2 = Carbon::parse($date2);
        return $carbonDate1->month !== $carbonDate2->month && $carbonDate1->year !== $carbonDate2->year;
    }

    public function updateExpense(Request $request)
    {
        try {
            Log::info("updating expense", ["req_body" => request()->all()]);
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

            Log::info("update expense success");
            return $this->successResponse($expense, 'Expense updated');
        } catch (\Exception $exception) {
            Log::error("update expense failed", ['exception' => $exception]);
            return $this->errorResponse($exception);
        }
    }

    public function bulkDeleteExpense(Request $request)
    {
        try {
            Log::info("deleting expense", ["req_body" => request()->all()]);
            $this->validate($request, [
                'ids' => 'required|array|min:1',
            ]);

            // bulk delete expense
            Expense::destroy($request->ids);

            Log::info("delete expense success");
            return $this->successResponse(null, 'Expenses deleted');
        } catch (\Exception $exception) {
            Log::error("delete expense failed", ['exception' => $exception]);
            return $this->errorResponse($exception);
        }
    }
}
