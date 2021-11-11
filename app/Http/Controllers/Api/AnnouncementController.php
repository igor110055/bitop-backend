<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Traits\ListQueryTrait;
use App\Http\Resources\{
    AnnouncementResource,
    PinnedAnnouncementResource,
};
use App\Repos\Interfaces\{
    AnnouncementRepo,
    AnnouncementReadRepo,
};

class AnnouncementController extends AuthenticatedController
{
    use ListQueryTrait;

    public function __construct(AnnouncementRepo $ar, AnnouncementReadRepo $arr)
    {
        parent::__construct();
        $this->AnnouncementRepo = $ar;
        $this->AnnouncementReadRepo = $arr;
    }

    public function getAnnouncements(Request $request)
    {
        $result = $this->AnnouncementRepo
            ->getAnnounced(
                $this->inputLimit(),
                $this->inputOffset()
            );
        return $this->paginationResponse(
            AnnouncementResource::collection($result['data']),
            $result['filtered'],
            $result['total']
        );
    }

    public function getPinnedAnnouncement()
    {
        $pinned = $this->AnnouncementRepo->getPinned();
        if (is_null($pinned)) {
            return null;
        } else {
            return new PinnedAnnouncementResource($pinned);
        }
    }

    public function getAnnouncement(string $id)
    {
        $announcement = $this->AnnouncementRepo->findOrFail($id);
        $user = auth()->user();
        if (is_null($this->AnnouncementReadRepo->getRead($user, $announcement))) {
            $this->AnnouncementReadRepo->createByUser($user, $announcement);
        }

        return new AnnouncementResource($announcement);
    }
}
