<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseType;
use App\Exceptions\EntityNotFoundException;
use App\Exceptions\ExpenseTypeException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExpenseDraftController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:user');
    }

    private function findUser()
    {
        return User::find(auth('user')->user()->id);
    }

    private function findExpenseDraft($user, $id)
    {
        $expense = $user->expenseDrafts()->where([
            ['id', $id]
        ])->first();

        if (!$expense) {
            throw new EntityNotFoundException('Expense draft not found');
        }

        return $expense;
    }

    public function getExpenseDrafts(Request $request)
    {
        try {
            Log::info("fetching expense drafts");
            $user = User::find(auth('user')->user()->id);
            if (!$user) {
                throw new \Exception('User not found');
            }
            Log::info("fetch expense drafts success");
            return $this->successResponse($user->expenseDrafts);
        } catch (\Exception $exception) {
            Log::error("fetch expense drafts failed", ['exception' => $exception]);
            return $this->errorResponse($exception);
        }
    }

    public function updateExpenseDraft(Request $request)
    {
        try {
            $this->validate($request, [
                'id' => 'required',
                'type' => 'required',
                'name' => 'required|string|max:100',
                'price' => 'required|gt:0',
                'date' => 'required|date',
            ]);

            Log::info("updating expense draft", ['request_body' => $request->all()]);

            if ($request->type) {
                $type = ExpenseType::coerce($request->type);
                if (!$type) {
                    throw new ExpenseTypeException('expense type invalid');
                }
            }

            $expense = $this->findExpenseDraft($this->findUser(), $request->id);
            $expense->name = $request->name ? $request->name : $expense->name;
            $expense->type = $request->type ? $request->type : $expense->type;
            $expense->price = $request->price ? $request->price : $expense->price;
            $expense->date = $request->date ? $request->date : $expense->date;
            $expense->notes = $request->notes ? $request->notes : $expense->notes;
            $expense->save();

            Log::info("update expense draft success");
            return $this->successResponse($expense, 'Expense draft updated');
        } catch (\Exception $exception) {
            Log::error("update expense draft failed", ['exception' => $exception]);
            return $this->errorResponse($exception);
        }
    }


    public function approveExpenseDraft(Request $request)
    {
        try {
            $this->validate($request, [
                'id' => 'required',
            ]);

            Log::info("approving expense draft", ['request_body' => $request->all()]);

            $user = $this->findUser();

            $expenseDraft = $this->findExpenseDraft($user, $request->id);
            $expenseDraft->status = "approved";
            $expenseDraft->save();
            Log::info("expense draft approved");

            Log::info("inserting expense draft as expense");
            $user->expenses()->create([
                'name' => $expenseDraft->name,
                'type' => $expenseDraft->type,
                'price' => $expenseDraft->price,
                'date' => $expenseDraft->date,
                'draft_id' => $expenseDraft->id
            ]);
            Log::info("expense draft inserted as expense");
            return $this->successResponse(null, 'Expense draft approved');
        } catch (\Exception $exception) {
            Log::error("approving expense draft failed", ['exception' => $exception]);
            return $this->errorResponse($exception);
        }
    }

    public function denyExpenseDraft(Request $request)
    {
        try {
            $this->validate($request, [
                'id' => 'required',
            ]);

            Log::info("denying expense draft", ['request_body' => $request->all()]);

            $expenseDraft = $this->findExpenseDraft($this->findUser(), $request->id);

            if ($expenseDraft->status == 'approved') {
                Log::info("expense draft has been approved before, deleting expense data related to it");
                $expenseDraft->expense()->delete();
                Log::info("expense deleted");
            }

            $expenseDraft->status = "denied";
            $expenseDraft->save();

            Log::info("expense draft denied");
            return $this->successResponse(null, 'Expense draft denied');
        } catch (\Exception $exception) {
            Log::error("denying expense draft failed", ['exception' => $exception]);
            return $this->errorResponse($exception);
        }
    }
}
