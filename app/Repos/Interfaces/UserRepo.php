<?php

namespace App\Repos\Interfaces;

use Carbon\Carbon;
use App\Models\{
    User,
    Verification,
    UserLock,
};

interface UserRepo
{
    public function find($id);
    public function findOrFail($id);
    public function findByEmail(string $email);
    public function findByEmailOrFail(string $email);
    public function findByMobile(string $mobile);
    public function findByMobileOrFail(string $mobile);
    public function create(array $values, Verification $email_verification, Verification $mobile_verification);
    public function setPassword(User $user, string $password);
    public function setSecurityCode(User $user, string $security_code);
    public function getRecentLogin(User $user);

    # User Lock related
    # ---------------------------
    public function createUserLock(User $user, string $type, Carbon $expired = null);
    public function authEventRecordLock(User $user, string $event);
    public function getFailedCount(User $user, string $event);
    public function getUserLocks(User $user, string $type = null, string $ip = null);
    public function getUserLock(User $user, string $type = null, string $ip = null);
    public function checkAdminUserLock(User $user);
    public function checkUserLock(User $user);
    public function checkUserFeatureLock(User $user, string $type);
    public function getAllUserLocks();
    public function unlockUserLock(UserLock $lock, bool $force = false);
    # ---------------------------

    public function getAllUsers();
    public function getUsersByBatch(
        $num,
        $group = null,
        $status = null,
        $keyword = null,
        callable $operate
    );
    public function getUserCount();
    public function getCurrentLoginUserCount();
    public function getFilteringQuery($group = null, $status = null, $keyword = null);
    public function queryStatus($query, $status = null);
    public function querySearch($query, $keyword = null);
    public function update(User $user, $values);
    public function getRoot();
    public function checkUsernameAvailability(string $username, $user_to_check = null);
    public function getNextUserForAuthentication();
    public function setAttribute(User $user, array $array);
    public function updateOrderCount(User $user, bool $complete);
}
