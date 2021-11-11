<?php

namespace App\Jobs\Fcm;

use App\Repos\Interfaces\DeviceTokenRepo;
use App\Models\User;

trait FcmTrait
{
    protected function getUserActiveTokens(User $user)
    {
        return app()
            ->make(DeviceTokenRepo::class)
            ->getUserActiveTokens($user)
            ->pluck('token')
            ->all();
    }

    protected function handleResponse(array $tokens, string $response)
    {
        if (!config('services.fcm.key')) {
            return;
        }
        $res = json_decode($response, true);
        if (data_get($res, 'success') !== count($tokens)) { # failure happens
            for ($i = 0; $i < count($tokens); $i++) {
                \Log::error("Fcm push notification receive 200 but fail", ['token' => data_get($tokens, "$i"), 'error_message' => data_get($res, "results.$i.error")]);
            }
        }
    }
}
