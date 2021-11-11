<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

use App\Repos\Interfaces\BankRepo;

class AvailableBankId implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($nationality)
    {
        $this->nationality = $nationality;
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
        $bank_repo = app()->make(BankRepo::class);
        $bank_ids = $bank_repo->getBankListIdByNationality($this->nationality);
        return $bank_ids->contains($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Unsupported :attribute in $this->nationality bank list.";
    }
}
