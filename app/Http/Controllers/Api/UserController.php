<?php

namespace App\Http\Controllers\Api;

use App\Repos\Interfaces\UserRepo;
use App\Http\Resources\{
    UserResource,
};


class UserController extends AuthenticatedController
{
    public function __construct(UserRepo $UserRepo)
    {
        parent::__construct();
        $this->UserRepo = $UserRepo;
    }

    public function show(string $id)
    {
        $user = $this->UserRepo->findOrFail($id);
        return new UserResource($user);
    }
}
