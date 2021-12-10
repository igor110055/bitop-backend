<?php

namespace App\Repos\DB;

use DB;
use Carbon\Carbon;
use App\Models\{
    Announcement,
    AnnouncementContent,
};

class AnnouncementRepo implements \App\Repos\Interfaces\AnnouncementRepo
{
    protected $announcement;

    public function __construct(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    public function find($id)
    {
        return $this->announcement->find($id);
    }

    public function findOrFail($id)
    {
        return $this->announcement->findOrFail($id);
    }

    public function create(array $values, string $locale = AnnouncementContent::LOCALE_ZHCN)
    {
        return DB::transaction(function () use ($values, $locale) {
            $announcement = $this->announcement->create($values);
            $this->createContent($announcement, $locale, $values['title'], $values['content']);
            return $announcement->refresh();
        });
    }

    public function createContent(Announcement $announcement, string $locale, $title, $content)
    {
        $announcement->announcement_contents()->create([
            'locale' => $locale,
            'title' => $title,
            'content' => $content,
        ]);
    }

    public function update(Announcement $announcement, array $values)
    {
        return $announcement->update($values);
    }

    public function updateContent(AnnouncementContent $ac, array $values)
    {
        return $ac->update($values);
    }

    public function getLocaleContent(Announcement $announcement, string $locale)
    {
        foreach($announcement->announcement_contents as $content) {
            if ($content->locale === $locale) {
                return $content;
            }
        }
        return null;
    }

    public function getAnnounced(
        int $limit,
        int $offset
    ) {
        $query = $this->announcement
            ->where('released_at', '<', Carbon::now());

        $total = $query->count();
        $data = $query
            ->latest('released_at')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'total' => $total,
            'filtered' => $data->count(),
            'data' => $data,
        ];
    }

    public function getPinned()
    {
        return $this->announcement
            ->whereNotNull('pin_end_at')
            ->where('released_at', '<', Carbon::now())
            ->latest('released_at')
            ->where('pin_end_at', '>', Carbon::now())
            ->first();
    }

    public function getAll()
    {
        return $this->announcement
            ->withTrashed()
            ->latest('created_at')
            ->get();
    }

    public function cancel(Announcement $announcement)
    {
        return $announcement->delete();
    }
}
