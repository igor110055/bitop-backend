<?php

namespace App\Repos\DB;

use App\Models\{
    Announcement,
    AnnouncementRead,
    User,
};

class AnnouncementReadRepo implements \App\Repos\Interfaces\AnnouncementReadRepo
{
    protected $read;

    public function __construct(AnnouncementRead $read) {
        $this->read = $read;
    }

    public function find(string $id)
    {
        return $this->read->find($id);
    }

    public function findOrFail(string $id)
    {
        return $this->read->findOrFail($id);
    }

    public function createByUser(User $user, Announcement $announcement)
    {
        return $user->announcement_reads()->create([
            'announcement_id' => $announcement->id,
        ]);
    }

    public function getRead(User $user, Announcement $announcement)
    {
        return $this->read
            ->where('user_id', $user->id)
            ->where('announcement_id', $announcement->id)
            ->first();
    }
}
