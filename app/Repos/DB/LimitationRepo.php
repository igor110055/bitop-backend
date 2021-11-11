<?php

namespace App\Repos\DB;

use Dec\Dec;
use App\Models\{
    Limitation,
    User,
    Group,
};

class LimitationRepo implements \App\Repos\Interfaces\LimitationRepo
{
    public function __construct(Limitation $limitation)
    {
        $this->limitation = $limitation;
    }

    public function find($id)
    {
        return $this->limitation->find($id);
    }

    public function findOrFail($id)
    {
        return $this->limitation->findOrFail($id);
    }

    public function create(array $values)
    {
        return $this->limitation->create($values);
    }

    protected function getQuery($query, $type, $coin)
    {
        return $query
            ->where('type', $type)
            ->where('coin', $coin)
            ->where('is_active', true)
            ->latest('created_at')
            ->first();
    }

    public function getLatestLimitationByClass($type, $coin, $limitable)
    {
        return $this->getQuery($limitable->limitations(), $type, $coin);
    }

    public function getLatestLimitation($type, $coin, $limitable = null)
    {
        if ($limitable instanceof User) {
            $user_limit = $this->getQuery($limitable->limitations(), $type, $coin);
            if ($user_limit) {
                return $user_limit;
            }
            $group_limit = $this->getQuery(
                $limitable->group->limitations(),
                $type,
                $coin
            );
            if ($group_limit) {
                return $group_limit;
            }
        } elseif ($limitable instanceof Group) {
            $group_limit = $this->getQuery($limitable->limitations(), $type, $coin);
            if ($group_limit) {
                return $group_limit;
            }
        }
        return $this->limitation
            ->where('type', $type)
            ->where('coin', $coin)
            ->whereNull('limitable_id')
            ->whereNull('limitable_type')
            ->where('is_active', true)
            ->latest('created_at')
            ->first();
    }

    public function checkLimitation(User $user, $type, $coin, $amount)
    {
        $limitation = $this->getLatestLimitation($type, $coin, $user);
        if (is_null($limitation)) {
            return true;
        }

        if ($type === Limitation::TYPE_WITHDRAWAL) {
            if ($user->two_factor_auth) {
                $limitation->max = Dec::mul($limitation->max, config('core.two_factor_auth.withdrawal_limit'));
            }
        }

        if (Dec::lte($limitation->min, $amount) and Dec::lte($amount, $limitation->max)) {
            return true;
        }
        return false;
    }
}
