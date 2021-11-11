<?php

namespace App\Repos\DB;

use App\Models\{
    User,
    GroupApplication,
};

class GroupApplicationRepo implements \App\Repos\Interfaces\GroupApplicationRepo
{
    protected $group_application;

    public function __construct(GroupApplication $ga) {
        $this->group_application = $ga;
    }

    public function find(string $id)
    {
        return $this->group_application->find($id);
    }

    public function findOrFail(string $id)
    {
        return $this->group_application->findOrFail($id);
    }

    public function createByUser(User $user, string $group_name, string $description)
    {
        return $user->group_applications()->create([
            'group_name' => $group_name,
            'description' => $description,
        ]);
    }

    public function update(GroupApplication $application, array $values)
    {
        $this->group_application->where('id', $application->id)->update($values);
    }

    public function getProcessingCount()
    {
        return $this->group_application
            ->where('status', GroupApplication::STATUS_PROCESSING)
            ->count();
    }

    public function getAll()
    {
        return $this->group_application->get();
    }
}
