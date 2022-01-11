<?php

namespace App\Repos\DB;

use App\Exceptions\{
    Core\UnknownError,
};
use App\Models\{
    User,
    DeviceToken,
};

class DeviceTokenRepo implements \App\Repos\Interfaces\DeviceTokenRepo
{
    protected $token;

    public function __construct(DeviceToken $token)
    {
        $this->token = $token;
    }

    public function find($id)
    {
        return $this->token->find($id);
    }

    public function findOrFail($id)
    {
        return $this->token->findOrFail($id);
    }

    public function update(DeviceToken $token, array $values)
    {
        $this->token
            ->where('id', data_get($token, 'id', $token))
            ->update($values);
    }

    public function create(array $values)
    {
        return $this->token->create($values);
    }

    public function changeActivation(DeviceToken $token, bool $status)
    {
        return $this->token
            ->where('id', $token->id)
            ->where('is_active', '!=', $status)
            ->update([
                'is_active' => $status,
            ]);
    }

    public function getUnique(array $values, User $user = null)
    {
        return $this->token
            ->when($user, function ($query, $user) {
                return $query->where('user_id', $user->id);
            })
            ->where('platform', $values['platform'])
            ->where('service', $values['service'])
            ->where('token', $values['token'])
            ->first();
    }

    public function getUserActiveTokens(User $user, $platform = null, $service = null)
    {
        return $this->token
            ->where('user_id', $user->id)
            ->when($service, function ($query, $service)  {
                return $query->where('service', $service);
            })
            ->when($platform, function ($query, $platform)  {
                return $query->where('platform', $platform);
            })
            ->where('is_active', true)
            ->get();
    }
}
