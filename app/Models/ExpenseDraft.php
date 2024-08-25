<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'notes'
    ];

    protected $hidden = [
        'user_id',
        "created_at",
        "updated_at"
    ];

    // Define relationships
    /**
     * Get the user that owns the expense draft.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
