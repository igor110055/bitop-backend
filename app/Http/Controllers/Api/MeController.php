<?php

namespace App\Http\Controllers\Api;

use Throwable;

use Illuminate\Http\Request;

use App\Http\Resources\{
    MeResource,
};
use App\Http\Requests\{
    UserUpdateRequest,
};
use App\Repos\Interfaces\{
    UserRepo,
};


class MeController extends AuthenticatedController
{
    public function __construct(
        UserRepo $UserRepo
    ) {
        parent::__construct();
        $this->UserRepo = $UserRepo;
    }

    public function show()
    {
        return new MeResource(auth()->user());
    }

    public function update(UserUpdateRequest $request)
    {
        $user = auth()->user();
        $values = $request->validated();

        if ($locale = data_get($values, 'locale')) {
            $this->UserRepo->update($user, [
                'locale' => $locale,
            ]);
        }
        return response(null, 204);
    }
}
