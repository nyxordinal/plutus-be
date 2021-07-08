<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseType extends Model
{
    /**
     * Get expenses that fall into this type of expense.
     */
    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
