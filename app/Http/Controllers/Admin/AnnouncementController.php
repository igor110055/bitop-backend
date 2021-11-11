<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Repos\Interfaces\{
    AnnouncementRepo,
    UserRepo,
};
use App\Models\{
    Announcement,
    Authentication,
};
use App\Jobs\BroadcastEmailJob;

class AnnouncementController extends AdminController
{
    public function __construct(AnnouncementRepo $AnnouncementRepo, UserRepo $UserRepo)
    {
        $this->AnnouncementRepo = $AnnouncementRepo;
        $this->UserRepo = $UserRepo;
        $this->tz = config('core.timezone.default');
    }

    public function index()
    {
        return view('admin.announcements', [
            'announcements' => $this->AnnouncementRepo->getAll(),
        ]);
    }

    public function show(Announcement $announcement)
    {
        $contents = [];
        foreach($announcement->announcement_contents as $content) {
            $contents[$content->locale] = [
                'title' => $content->title,
                'content' => strip_tags($content->content),
            ];
        }

        return view('admin.announcement', [
            'announcement' => $announcement,
            'contents' => $contents,
            'locale' => [
                'en' => 'EN',
                'zh-tw' => 'ZH-TW',
                'zh-cn' => 'ZH-CN',
            ],
        ]);
    }

    public function update(Announcement $announcement, Request $request)
    {
        $locale = $request->input('locale');
        $title = $request->input('title');
        $content = $request->input('text');

        $release_time = Carbon::parse($request->input('release_time'), $this->tz);
        if ($pin_time = $request->input('pin_time')) {
            $pin_time = Carbon::parse($pin_time, $this->tz);
        }

        if ($announcement_content = $this->AnnouncementRepo->getLocaleContent($announcement, $locale)) {
            $this->AnnouncementRepo->updateContent($announcement_content, [
                'title' => $title,
                'content' => $content,
            ]);
        } else {
            $this->AnnouncementRepo->createContent(
                $announcement,
                $locale,
                $title,
                $content
            );
        }
        $this->AnnouncementRepo->update($announcement, [
            'released_at' => $release_time,
            'pin_end_at' => $pin_time ?? null,
        ]);

        return response($announcement, 200);
    }

    public function store(Request $request)
    {
        if ($release_time = $request->input('release_time')) {
            $release_time = Carbon::parse($release_time, $this->tz);
        }
        $this->AnnouncementRepo->create([
            'title' => $request->input('title'),
            'content' => $request->input('text'),
            'released_at' => $release_time ?? Carbon::now(),
            'pin_end_at' => $pin_time ?? null,
        ]);
        return $request;
    }

    public function cancel(Announcement $announcement)
    {
        $this->AnnouncementRepo->cancel($announcement);
        return response(null, 204);
    }

    public function emailBroadcast(Announcement $announcement)
    {
        $this->UserRepo->getUsersByBatch(
            config('core.broadcast.user_chunk'), // chunk num
            null, // user query by group
            Authentication::PASSED, // user status query by authentication status
            null, // user status query by keyword
            function ($users) use ($announcement) {
                BroadcastEmailJob::dispatch($users, $announcement);
            }
        );
        return response($announcement, 200);
    }
}
