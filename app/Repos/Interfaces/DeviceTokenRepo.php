<?php

namespace App\Repos\Interfaces;

use App\Models\{
    User,
    DeviceToken,
};

interface DeviceTokenRepo
{
    public function find($id);
    public function findOrFail($id);
    public function update(DeviceToken $token, array $values);
    public function create(array $values);
    public function changeActivation(DeviceToken $token, bool $status);
    public function getUnique(array $values, User $user = null);
    public function getUserActiveTokens(User $user, $platform = null, $service = null);
}
