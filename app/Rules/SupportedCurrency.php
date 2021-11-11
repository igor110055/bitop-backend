<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class SupportedCurrency implements Rule
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
        $supported_currency = config("core")["nationality"]["$this->nationality"]["currency"];
        if (!in_array($value, $supported_currency)) {
            return false;
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Contained unsupported currency in $this->nationality currency list.";
    }
}
