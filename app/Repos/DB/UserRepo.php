<?php

namespace App\Repos\DB;

use Carbon\Carbon;
use DB;
use App\Exceptions\{
    Core\BadRequestError,
    UnavailableStatusError,
};
use App\Models\{
    User,
    Verification,
    UserLog,
    UserLock,
    Authentication,
};

class UserRepo implements \App\Repos\Interfaces\UserRepo
{
    protected $user;

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function find($id)
    {
        return $this->user->find($id);
    }

    public function findOrFail($id)
    {
        return $this->user->findOrFail($id);
    }

    public function findByEmail(string $email)
    {
        return $this->user->where('email', $email)->first();
    }

    public function findByEmailOrFail(string $email)
    {
        return $this->user->where('email', $email)->firstOrFail();
    }

    public function findByMobile(string $mobile)
    {
        return $this->user->where('mobile', $mobile)->first();
    }

    public function findByMobileOrFail(string $mobile)
    {
        return $this->user->where('mobile', $mobile)->firstOrFail();
    }

    public function create(array $values, Verification $email_verification, $mobile_verification)
    {
        $user = $this->user->create($values);
        if ($email_verification) {
            $user->email_verification_id = $email_verification->id;
        }
        if ($mobile_verification) {
            $user->mobile_verification_id = $mobile_verification->id;
        }
        return $user;
    }

    public function createUserLock(User $user, string $type, Carbon $expired = null)
    {
        if (is_null($expired)) {
            $time = config("core.users.user-lock.$type.time");
            $expired = Carbon::now()->addSeconds($time)->format('Uv');
        }

        $data = [
            'type' => $type,
            'expired_at' => $expired,
        ];
        if (in_array($type, UserLock::RECORD_IP)) {
            $data['ip'] = request_ip();
        }
        return $user->user_locks()->create($data);
    }

    public function setPassword(User $user, string $password) : bool
    {
        return $user->update([
            'password' => \Hash::make($password)
        ]);
    }

    public function setSecurityCode(User $user, string $security_code) : bool
    {
        return $user->update([
            'security_code' => \Hash::make($security_code)
        ]);
    }

    public function getRecentLogin(User $user, $skip = 0)
    {
        return $user->user_logs()
            ->where('message', UserLog::LOG_IN)
            ->latest()
            ->skip($skip)
            ->first();
    }

    public function authEventRecordLock(User $user, string $event)
    {
        if (!in_array($event, UserLog::FAIL_EVENTS)) {
            throw new BadRequestError;
        }

        user_log($event, ['id' => $user->id], request());
        if ($this->getFailedCount($user, $event) >= config('core.users.fail-max')[$event]) {
            # lock
            if ($event === UserLog::PASSWORD_FAIL) {
                $lock = $this->createUserLock($user, UserLock::LOGIN);
                user_log(UserLog::LOG_IN_LOCK, ['id' => $user->id]);
                return $lock;
            } elseif ($event === UserLog::SECURITY_CODE_FAIL) {
                $lock = $this->createUserLock($user, UserLock::SECURITY_CODE);
                user_log(UserLog::SECURITY_CODE_LOCK, ['id' => $user->id]);
                return $lock;
            } elseif ($event === UserLog::ADMIN_LOG_IN_PASSWORD_FAIL) {
                $lock = $this->createUserLock($user, UserLock::BACKEND_LOGIN_PASSWORD);
                user_log(UserLog::ADMIN_LOG_IN_LOCK, ['id' => $user->id]);
                return $lock;
            } elseif ($event === UserLog::ADMIN_LOG_IN_2FA_FAIL) {
                $lock = $this->createUserLock($user, UserLock::BACKEND_LOGIN_2FA);
                user_log(UserLog::ADMIN_LOG_IN_LOCK, ['id' => $user->id]);
                return $lock;
            }
        }
    }

    public function getFailedCount(User $user, string $event)
    {
        $search = [
            UserLog::PASSWORD_FAIL => [
                UserLog::LOG_IN,
                UserLog::LOG_IN_UNLOCK
            ],
            UserLog::SECURITY_CODE_FAIL => [
                UserLog::SECURITY_CODE_SUCCESS,
                UserLog::SECURITY_CODE_UNLOCK
            ],
            UserLog::ADMIN_LOG_IN_PASSWORD_FAIL => [
                UserLog::ADMIN_LOG_IN,
                UserLog::ADMIN_LOG_IN_UNLOCK,
            ],
            UserLog::ADMIN_LOG_IN_2FA_FAIL => [
                UserLog::ADMIN_LOG_IN,
                UserLog::ADMIN_LOG_IN_UNLOCK,
            ],
        ];

        if ($event === UserLog::PASSWORD_FAIL or $event === UserLog::ADMIN_LOG_IN_PASSWORD_FAIL) {
            $with_ip = true;
        }

        $log = $user->user_logs()
            ->whereIn('message', $search[$event])
            ->latest('created_at')
            ->first();
        if (!$log) {
            return $user->user_logs()
                ->where('message', $event)
                ->when(isset($with_ip), function ($query) {
                    return $query->where('remote_addr', request_ip());
                })
                ->count();
        }
        return $user->user_logs()
            ->where('created_at', '>', $log->created_at)
            ->where('message', $event)
            ->when(isset($with_ip), function ($query) {
                return $query->where('remote_addr', request_ip());
            })
            ->count();
    }

    public function getUserLock(User $user, string $type = null, string $ip = null)
    {
        return $user->user_locks()
            ->when($type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->when($ip, function ($query, $ip) {
                return $query->where('ip', $ip);
            })
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    public function getUserLocks(User $user, string $type = null, string $ip = null)
    {
        return $user->user_locks()
            ->when($type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->when($ip, function ($query, $ip) {
                return $query->where('ip', $ip);
            })
            ->where('is_active', true)
            ->get();
    }

    public function checkAdminUserLock(User $user)
    {
        foreach (UserLock::BACKEND_TYPES as $type) {
            if ($type === UserLock::BACKEND_LOGIN_PASSWORD) {
                $records = $this->getUserLocks($user, $type, request_ip());
            } else {
                $records = $this->getUserLocks($user, $type);
            }
            if ($records->isNotEmpty()) {
                return true;
            }
        }
        return false;
    }

    public function checkUserLock(User $user)
    {
        foreach (UserLock::FRONTEND_TYPES as $type) {
            if ($type === UserLock::LOGIN) {
                $records = $this->getUserLocks($user, $type, request_ip());
            } else {
                $records = $this->getUserLocks($user, $type);
            }
            if ($records->isNotEmpty()) {
                return true;
            }
        }
        return false;
    }

    public function checkUserFeatureLock(User $user, string $type)
    {
        if ($lock = $this->getUserLock($user, $type)) {
            return true;
        }
        return false;
    }

    public function getAllUserLocks()
    {
        return UserLock::where('is_active', true)
            ->orderBy('created_at')
            ->get();
    }

    public function unlockUserLock(UserLock $lock, bool $force = false)
    {
        if ($force) {
            $lock->update(['is_active' => false]);
        } else {
            DB::transaction(function () use ($lock) {
                if ($lock->expired_at->lt(Carbon::now())) {
                    $lock->update(['is_active' => false]);
                    if ($lock->type === UserLock::LOGIN) {
                        user_log(UserLog::LOG_IN_UNLOCK, ['id' => $lock->user_id]);
                    } elseif ($lock->type === UserLock::SECURITY_CODE) {
                        user_log(UserLog::SECURITY_CODE_UNLOCK, ['id' => $lock->user_id]);
                    } elseif ($lock->type === UserLock::ADMIN) {
                        user_log(UserLog::SECURITY_CODE_UNLOCK, ['id' => $lock->user_id]);
                    } elseif ($lock->type === UserLock::BACKEND_LOGIN_PASSWORD or $lock->type === UserLock::BACKEND_LOGIN_2FA) {
                        user_log(UserLog::ADMIN_LOG_IN_UNLOCK, ['id' => $lock->user_id]);
                    }
                }
            });
        }
    }

    public function getAllUsers()
    {
        return $this->user->get();
    }

    public function getUsersByBatch(
        $num,
        $group = null,
        $status = null,
        $keyword = null,
        callable $operate
    ) {
        return $this->getFilteringQuery($group, $status, $keyword)
            ->chunk($num, $operate);
    }

    public function getUserCount()
    {
        return $this->user->count();
    }

    public function getCurrentLoginUserCount()
    {
        $time = config('core.users.login_timeframe');
        return UserLog::where('message', UserLog::LOG_IN)
            ->where('created_at', '>', Carbon::now()->subMinute($time))
            ->get()
            ->groupBy('user_id')
            ->count();
    }

    public function getFilteringQuery($group = null, $status = null, $keyword = null)
    {
        $query = $this->user;
        $query = $this->queryGroup($query, $group);
        $query = $this->queryStatus($query, $status);
        $query = $this->querySearch($query, $keyword);
        return $query;
    }

    public function queryGroup($query, $group = null)
    {
        if ($group) {
            $query = $query->where('group_id', data_get($group, 'id', $group));
        }
        return $query;
    }

    public function queryStatus($query, $status = null)
    {
        if ($status and in_array($status, Authentication::STATUS)) {
            $query = $query->where('authentication_status', $status);
        }
        return $query;
    }

    public function querySearch($query, $keyword = null)
    {
        if ($keyword and is_string($keyword)) {
            $query = $query->where(function ($query) use ($keyword) {
                $like = "%{$keyword}%";
                return $query
                    ->orWhere('id', 'like', $like)
                    ->orWhere('email', 'like', $like)
                    ->orWhere('username', 'like', $like)
                    ->orWhere('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like);
            });
        }
        return $query;
    }

    public function update(User $user, $values)
    {
        return $user->update($values);
    }

    public function getRoot()
    {
        if (!env('ROOT_ID')) {
            return null;
        }
        return $this->find(env('ROOT_ID'));
    }

    public function checkUsernameAvailability(string $username, $user_to_check = null)
    {
        $users = $this->user->where('username', $username)->get();
        if (is_null($user_to_check)) {
            return ($users->count() === 0);
        } else {
            foreach ($users as $user) {
                if ($user->id !== data_get($user_to_check, 'id', $user_to_check)) {
                    return false;
                }
            }
            return true;
        }
    }

    public function getNextUserForAuthentication()
    {
        return $this->user->where('authentication_status', Authentication::PROCESSING)->oldest('updated_at')->first();
    }

    public function setAttribute(User $user, array $array)
    {
        if ($this->user
            ->where('id', $user->id)
            ->update($array) !== 1) {
            throw new UnavailableStatusError;
        }
    }

    public function updateOrderCount(User $user, bool $complete)
    {
        if ($complete) {
            if ($this->user
                ->where('id', $user->id)
                ->update([
                    'valid_order_count' => DB::raw('valid_order_count + 1'),
                    'complete_order_count' => DB::raw('complete_order_count + 1'),
                ]) !== 1) {
                throw new UnavailableStatusError;
            }
        } else {
            if ($this->user
                ->where('id', $user->id)
                ->update([
                    'valid_order_count' => DB::raw('valid_order_count + 1'),
                ]) !== 1) {
                throw new UnavailableStatusError;
            }
        }
    }
}
