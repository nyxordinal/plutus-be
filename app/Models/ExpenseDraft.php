<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExpenseDraft extends Model
{
    use HasFactory;

    protected $table = 'expense_drafts';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id',
        'user_id',
        'name',
        'type',
        'price',
        'date',
        'status',
        'notes',
        'message_id'
    ];

    protected $hidden = [
        'user_id',
        "created_at",
        "updated_at"
    ];

    /**
     * Get the user that owns the expense draft.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the expense that belongs to the expense draft.
     */
    public function expense(): HasOne
    {
        return $this->hasOne(Expense::class, 'draft_id');
    }
}
