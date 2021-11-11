<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

use App\Repos\Interfaces\AuthenticationRepo;

use App\Models\User;

class AvailableAuthFileId implements Rule
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
        $auth_repo = app()->make(AuthenticationRepo::class);
        $auth_file_ids = $auth_repo->getAuthFileIds($this->user);
        return $auth_file_ids->contains($value);
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return "Unsupported :attribute in user's uplaod file list";
    }
}
