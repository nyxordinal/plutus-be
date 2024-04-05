<?php

namespace App\Jobs;

use App\Enums\MailType;
use App\Mail\DailyExpenseLimitAlert;
use App\Mail\ExpenseLimitAlert;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class SendEmailJob extends Job
{
    protected string $email;
    protected User $user;
    protected $totalExpense;
    protected MailType $mailType;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $email, User $user, $totalExpense, MailType $mailType)
    {
        $this->email = $email;
        $this->user = $user;
        $this->totalExpense = $totalExpense;
        $this->mailType = $mailType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        switch ($this->mailType->value) {
            case MailType::ExpenseLimit:
                Mail::to($this->email)->send(new ExpenseLimitAlert($this->user, $this->totalExpense));
                break;
            case MailType::ExpenseDailyLimit:
                Mail::to($this->email)->send(new DailyExpenseLimitAlert($this->user, $this->totalExpense));
                break;
        }
    }
}
