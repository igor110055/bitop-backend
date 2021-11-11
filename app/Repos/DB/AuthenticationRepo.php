<?php

namespace App\Repos\DB;

use DB;

use App\Models\{
    User,
    Authentication,
    AuthenticationFile,
};

use Carbon\Carbon;

class AuthenticationRepo implements \App\Repos\Interfaces\AuthenticationRepo
{
    protected $auth;

    public function __construct(Authentication $auth, AuthenticationFile $auth_file) {
        $this->auth = $auth;
        $this->auth_file = $auth_file;
    }

    public function find($id)
    {
        return $this->auth->find($id);
    }

    public function findOrFail($id)
    {
        return $this->auth->findOrFail($id);
    }

    public function findFile($id)
    {
        return $this->auth_file->find($id);
    }

    public function createAuth(User $user, array $values)
    {
        $auth = $user->authentications()->create($values);
        if ($auth) {
            $auth->owner->update([
                'authentication_status' => Authentication::PROCESSING,
            ]);
        }
        return $auth;
    }

    public function createAuthFile(User $user, array $values)
    {
        return $user->authentication_files()->create($values);
    }

    public function associateAuthId(Authentication $auth, array $file_ids)
    {
        foreach ($file_ids as $id) {
            $file = $this->findFile($id);
            $file->authentication()->associate($auth);
            $file->save();
        }
    }

    public function getAuthFileIds(User $user)
    {
        return $user->authentication_files()
            ->orderBy('created_at')
            ->get()
            ->pluck('id');
    }

    public function getLatestAuth(User $user)
    {
        return $user->authentications()
            ->latest('created_at')
            ->first();
    }

    public function getLatestAuthFiles(User $user)
    {
        $auth = $this->getLatestAuth($user);
        if ($auth) {
            return $auth->authentication_files;
        }
        return null;
    }

    public function checkIsVerified(User $user)
    {
        $auth = $user->authentications()
            ->whereNotNull('verified_at')
            ->latest('created_at')
            ->first();
        if ($auth and $auth->status === Authentication::PASSED) {
            return true;
        }
        return false;
    }

    public function checkAuthStatus(User $user)
    {
        $auth = $user->authentications()
            ->latest('updated_at')
            ->first();
        if (!$auth) {
            return Authentication::UNAUTHENTICATED;
        } else {
            return $auth->status;
        }
    }

    public function approve(Authentication $auth)
    {
        return DB::transaction(function () use ($auth) {
            $update = $auth->update([
                'verified_at' => Carbon::now()->format('Uv'),
                'status' => Authentication::PASSED,
            ]);
            if ($update) {
                return $auth->owner->update([
                    'first_name' => $auth->first_name,
                    'last_name' => $auth->last_name,
                    'username' => $auth->username,
                    'security_code' => $auth->security_code,
                    'authentication_status' => Authentication::PASSED,
                ]);
            }
        });
    }

    public function reject(Authentication $auth)
    {
        return DB::transaction(function () use ($auth) {
            $update = $auth->update([
                'verified_at' => Carbon::now()->format('Uv'),
                'status' => Authentication::REJECTED,
            ]);
            if ($update) {
                return $auth->owner->update([
                    'authentication_status' => Authentication::REJECTED,
                ]);
            }
        });
    }
}
