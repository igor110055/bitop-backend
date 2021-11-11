<?php

namespace App\Repos\Interfaces;

use App\Models\{
    Group,
    GroupInvitation,
    User,
};

interface GroupRepo
{
    public function getAllGroups();
    public function find($id);
    public function findOrFail($id);
    public function getJoinableGroupIds();
    public function createInvitation(Group $group);
    public function getInvitationByCode(string $code);
    public function setInvitationUsedTime(GroupInvitation $inv);
    public function update(Group $group, $values);
    public function create($values);
    public function getBelongingGroupList(User $user);
    public function getGroupMembers(
        Group $group,
        int $limit,
        int $offset
    );
}
