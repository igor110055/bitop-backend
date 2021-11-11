<?php

namespace App\Repos\Interfaces;

use App\Models\{
    User,
    Authentication,
};

interface AuthenticationRepo
{
    public function find($id);
    public function findOrFail($id);
    public function findFile($id);
    public function createAuth(User $user, array $values);
    public function createAuthFile(User $user, array $values);
    public function associateAuthId(Authentication $auth, array $ids);
    public function getAuthFileIds(User $user);
    public function getLatestAuth(User $user);
    public function getLatestAuthFiles(User $user);
    public function checkIsVerified(User $user);
    public function checkAuthStatus(User $user);
    public function approve(Authentication $auth);
    public function reject(Authentication $auth);
}
