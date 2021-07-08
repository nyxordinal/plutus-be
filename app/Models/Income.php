<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['source', 'amount', 'date'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'user_id', "created_at", "updated_at"
    ];

    /**
     * Get the user that owns the income.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
