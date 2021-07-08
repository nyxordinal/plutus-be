<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Income extends Model
{
    /**
     * Get the user that owns the income.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
