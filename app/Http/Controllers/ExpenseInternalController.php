<?php

namespace App\Http\Controllers;

use App\Enums\ExpenseType;
use App\Exceptions\ExpenseTypeException;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class ExpenseInternalController extends Controller
{
    public function createExpenseInternal(Request $request)
    {
        try {
            $this->validate($request, [
                'user_id' => 'required|exists:users,id',
                'type' => 'required',
                'name' => 'required|string',
                'price' => 'required|numeric',
                'date' => 'required|date',
            ]);

            $clientId = $request->header('X-Client-Id');
            if (!$clientId) {
                throw ValidationException::withMessages(['Client ID' => 'Client ID is required']);
            }

            Log::info('Creating expense internal request', [
                'client_id' => $clientId,
                'request_body' => $request->all()
            ]);

            $type = ExpenseType::coerce($request->type);
            if (!$type) {
                throw new ExpenseTypeException('Expense type is invalid');
            }

            $user = User::find($request->user_id);
            if (!$user) {
                throw new \Exception('User not found');
            }

            $user->expenseDrafts()->create([
                'id' => Str::uuid(),
                'name' => $request->name,
                'type' => $type->value,
                'price' => $request->price,
                'date' => $request->date,
            ]);

            Log::info('Expense draft created successfully', ['user_id' => $user->id]);
            return $this->createdResponse(null, 'Expense created');
        } catch (\Exception $exception) {
            Log::error('Failed to create expense draft', [
                'error_message' => $exception->getMessage(),
                'request_body' => $request->all()
            ]);
            return $this->errorResponse($exception);
        }
    }
}
