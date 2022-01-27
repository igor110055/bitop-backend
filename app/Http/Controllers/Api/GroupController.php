<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\{
    AccessDeniedHttpException,
};

use App\Http\Controllers\Traits\ListQueryTrait;
use App\Models\Group;
use App\Repos\Interfaces\{
    GroupRepo,
    GroupApplicationRepo,
};
use App\Http\Resources\{
    GroupResource,
    GroupMembersResource,
};
use App\Http\Requests\ApplyNewGroupRequest;

class GroupController extends AuthenticatedController
{
    use ListQueryTrait;

    public function __construct(GroupRepo $gr, GroupApplicationRepo $gar)
    {
        parent::__construct();
        $this->GroupRepo = $gr;
        $this->GroupApplicationRepo = $gar;
        $this->middleware(
            'real_name.check',
            ['only' => ['applyNewGroup']]
        );

        $this->middleware(
            'userlock',
            ['only' => ['createInvitation']]
        );
    }

    public function index()
    {
        $groups = $this->GroupRepo->getBelongingGroupList(auth()->user());
        return GroupResource::collection($groups);
    }

    public function getGroupMembers(string $id, Request $request)
    {
        $group = $this->getAndCheckAuthorization($id);
        $result = $this->GroupRepo
            ->getGroupMembers(
                $group,
                $this->inputLimit(),
                $this->inputOffset()
            );
        return $this->paginationResponse(
            GroupMembersResource::collection($result['data']),
            $result['filtered'],
            $result['total']
        );
    }

    public function createInvitation(string $id)
    {
        $group = $this->getAndCheckAuthorization($id);
        $inv = $this->GroupRepo->createInvitation($group);
        $url = url("auth/register?invitation={$inv->invitation_code}");
        $inv->url = $url;
        return $inv;
    }

    public function applyNewGroup(ApplyNewGroupRequest $request)
    {
        $values = $request->validated();
        $group_application = $this->GroupApplicationRepo->createByUser(auth()->user(), $values['group_name'], $values['description']);
        return response(null, 201);
    }

    protected function getAndCheckAuthorization(string $id)
    {
        try {
            $group = $this->GroupRepo->findOrFail($id);
            if (data_get($group->owner, 'id') !== \Auth::id()) {
                throw new AccessDeniedHttpException;
            }
        } catch (\Throwable $e) {
            throw new AccessDeniedHttpException;
        }
        return $group;
    }
}
