<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DailyExpenseLimitAlert extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The user instance.
     *
     * @var \App\Models\User
     */
    public $user;

    /**
     * Current total expense.
     *
     * @var double
     */
    public $totalExpense;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(User $user, $totalExpense)
    {
        $this->user = $user;
        $this->totalExpense = $totalExpense;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('no-reply@nyxordinal.dev', 'Nyxordinal')
            ->subject('Important: Your expense exceeds your daily expense limit!')
            ->view('expense-daily-limit-alert');
    }
}
