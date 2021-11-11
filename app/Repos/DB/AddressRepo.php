<?php

namespace App\Repos\DB;

use App\Models\{
    Address,
    User,
};

class AddressRepo implements \App\Repos\Interfaces\AddressRepo
{
    protected $address;

    public function __construct(Address $address) {
        $this->address = $address;
        $this->coins = config('coin');
    }

    public function find($id)
    {
        return $this->address->find($id);
    }

    public function findOrFail($id)
    {
        return $this->address->findOrFail($id);
    }

    public function update(Address $address, $values)
    {
        return $address->update($values);
    }

    public function createByUser(User $user, $values)
    {
        $values['network'] = data_get($this->coins, "{$values['coin']}.network");
        return $user->addresses()->create($values);
    }

    public function getUserAddresses(User $user, $coin = null)
    {
        return $user->addresses()
            ->when($coin, function ($query, $coin) {
                return $query->where('coin', $coin);
            })
            ->get();
    }

    public function delete(Address $address)
    {
        return $address->delete();
    }
}
