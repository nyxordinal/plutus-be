<?php

namespace App\Jobs;

use App\Mail\ExpenseLimitAlert;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendEmailJob extends Job
{
    protected string $email;
    protected User $user;
    protected $totalExpense;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $email, User $user, $totalExpense)
    {
        $this->email = $email;
        $this->user = $user;
        $this->totalExpense = $totalExpense;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to($this->email)->send(new ExpenseLimitAlert($this->user, $this->totalExpense));
    }
}
