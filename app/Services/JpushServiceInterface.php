<?php

namespace App\Services;

use App\Models\User;

interface JpushServiceInterface
{
    public function sendMessageToUser(User $user, array $notification = null, array $data = null, array $option = null);
    public function sendMessage($platform ,$tokens, array $notification = null, array $data = null, array $option = null);
}
