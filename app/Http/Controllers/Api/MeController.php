<?php

namespace App\Http\Controllers\Api;

use Throwable;

use Illuminate\Http\Request;

use App\Http\Resources\{
    GroupResource,
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

        $this->middleware(
            'userlock',
            ['only' => ['show', 'update', 'getInvitationInfo']]
        );

        $this->middleware(
            'real_name.check',
            ['only' => ['getInvitationInfo']]
        );
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

    public function getInvitationInfo()
    {
        $user = auth()->user();
        $invitation = $this->UserRepo->findOrCreateInvitation($user);
        $url = url("auth/register?invitation={$invitation->id}");
        $invitees_count = $user->invitees()->count();
        $group = $user->groups()->first();

        return [
            'code' => $invitation->id,
            'url' => $url,
            'invitee_count' => $invitees_count,
            'group' => new GroupResource($group),
            'commission_percentage' => config('core.share.percentage.inviter'),
        ];
    }
}
