<?php

namespace App\Repos\DB;

use Spatie\Permission\Models\Role;
use App\Models\User;

class RoleRepo implements \App\Repos\Interfaces\RoleRepo
{
    protected $role;

    public function __construct(Role $role)
    {
        $this->role= $role;
    }

    public function getAll(string $guard = null)
    {
        if (is_null($guard)) {
            return $this->role->all();
        }
        return $this->role
            ->where('guard_name', $guard)
            ->get();
    }

    public function getAllRoles(string $guard = null) : array
    {
        if (is_null($guard)) {
            $res = [];
            foreach ($this->role->all() as $role) {
                $res[$role->guard_name][] = $role->name;
            }
            return $res;
        }
        return $this->role
            ->where('guard_name', $guard)
            ->pluck('name');
    }

    public function getUserRole(User $user, string $guard = null)
    {
        return $user->roles
            ->where('guard_name', $guard)
            ->first();
    }
}
