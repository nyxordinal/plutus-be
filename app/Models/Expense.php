<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'type', 'price', 'date', 'draft_id'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'user_id',
        "created_at",
        "updated_at"
    ];

    /**
     * Get the user that owns the expense.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
