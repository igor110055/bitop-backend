<?php

namespace App\Repos\Interfaces;

use App\Models\{
    Limitation,
    User,
};

interface LimitationRepo
{
    public function find($id);
    public function findOrFail($id);
    public function create(array $values);
    /*
     * function: getLatestLimitationByClass
     * get the latest limitation by class
     */
    public function getLatestLimitationByClass($type, $coin, $limitable);
    /*
     * function: getLatestLimitation
     * get the latest limitation
     * with the order of user => group => system
     */
    public function getLatestLimitation($type, $coin, $limitable = null);
    public function checkLimitation(User $user, $type, $coin, $amount);
}
