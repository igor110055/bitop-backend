<?php

namespace App\Repos\Interfaces;

use App\Models\{
    Address,
    User,
};

interface AddressRepo
{
    public function find($id);
    public function findOrFail($id);
    public function update(Address $address, $values);
    public function createByUser(User $user, $values);
    public function getUserAddresses(User $user, $coin = null);
    public function delete(Address $address);
}
