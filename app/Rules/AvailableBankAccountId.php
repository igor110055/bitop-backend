<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

use App\Repos\Interfaces\BankAccountRepo;
use App\Models\User;

class AvailableBankAccountId implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $bank_account_repo = app()->make(BankAccountRepo::class);
        $bank_account_ids = $bank_account_repo->getUserBankAccountIds($this->user);
        return $bank_account_ids->contains($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Unsupported :attribute in user's bank account list.";
    }
}
