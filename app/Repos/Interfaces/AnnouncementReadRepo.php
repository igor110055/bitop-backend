<?php

namespace App\Repos\Interfaces;

use App\Models\{
    User,
    Announcement,
};

interface AnnouncementReadRepo
{
    public function find(string $id);
    public function findOrFail(string $id);
    public function createByUser(User $user, Announcement $announcement);
    public function getRead(User $user, Announcement $announcement);
}
