<?php

namespace App\Repos\DB;

use App\Models\{
    Group,
    GroupInvitation,
    User,
};

use Carbon\Carbon;

class GroupRepo implements \App\Repos\Interfaces\GroupRepo
{
    protected $group;

    public function __construct(Group $group, GroupInvitation $gi) {
        $this->group = $group;
        $this->group_inv = $gi;
    }

    public function getAllGroups()
    {
        return $this->group->all();
    }

    public function find($id)
    {
        return $this->group->find($id);
    }

    public function findOrFail($id)
    {
        return $this->group->findOrFail($id);
    }

    public function getJoinableGroupIds()
    {
        return $this->group
            ->where('is_joinable', true)
            ->pluck('id');
    }

    public function createInvitation(Group $group)
    {
        $code = generate_code(
            GroupInvitation::CODE_LENGTH,
            GroupInvitation::CODE_TYPE_DIGIT_ALL
        );
        $time = config('core')['group_invitation']['expired_time'];
        return $group->group_invitations()->create([
            'invitation_code' => $code,
            'expired_at' => Carbon::now()->addSecond($time)->format('Uv'),
        ]);
    }

    public function getInvitationByCode(string $code)
    {
        return $this->group_inv
            ->where('invitation_code', $code)
            ->first();
    }

    public function setInvitationUsedTime(GroupInvitation $inv)
    {
        return $inv->update(['used_at' => Carbon::now()->format('Uv')]);
    }

    public function update(Group $group, $values)
    {
        return $group->update($values);
    }

    public function create($values)
    {
        return $this->group->create($values);
    }

    public function getBelongingGroupList(User $user)
    {
        return $this->group
            ->where('user_id', $user->id)
            ->get();
    }

    public function getGroupMembers(
        Group $group,
        int $limit,
        int $offset
    ) {
        $query = $group->users();

        $total = $query->count();
        $data = $query
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'total' => $total,
            'filtered' => $data->count(),
            'data' => $data,
        ];

        
    }
}
