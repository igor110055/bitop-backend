<?php

namespace App\Repos\Interfaces;

use App\Models\{
    Announcement,
    AnnouncementContent,
};

interface AnnouncementRepo
{
    public function find($id);
    public function findOrFail($id);
    public function create(
        array $values,
        string $locale = AnnouncementContent::LOCALE_EN
    );
    public function createContent(
        Announcement $announcement,
        string $locale,
        $title,
        $content
    );
    public function update(
        Announcement $announcement,
        array $values
    );
    public function updateContent(
        AnnouncementContent $ac,
        array $values
    );
    public function getLocaleContent(
        Announcement $announcement,
        string $locale
    );
    public function getAnnounced(
        int $limit,
        int $offset
    );
    public function getPinned();
    public function getAll();
    public function cancel(Announcement $announcement);
}
