<?php

namespace App\Repos\Interfaces;

use App\Models\User;

interface GroupApplicationRepo
{
    public function find(string $id);
    public function findOrFail(string $id);
    public function createByUser(User $user, string $group_name, string $description);
}
