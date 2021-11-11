<?php

namespace App\Repos\Interfaces;

use App\Models\User;

interface RoleRepo
{
    public function getAll(string $guard = null);
    public function getAllRoles(string $guard = null) : array;
    public function getUserRole(User $user, string $guard = null);
}
