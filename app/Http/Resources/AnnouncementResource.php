<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Repos\Interfaces\AnnouncementReadRepo;

class AnnouncementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $user = auth()->user();
        $repo = app()->make(AnnouncementReadRepo::class);
        if ($this->released_at->addDay(90) < Carbon::now()) {
            $read = true;
        } else {
            if(!is_null($repo->getRead($user, $this->resource))) {
                $read = true;
            } else {
                $read = false;
            }
        }

        $titles = [];
        $contents = [];
        foreach($this->announcement_contents as $content) {
            $titles[$content->locale] = $content->title;
            $contents[$content->locale] = $content->content;
        }

        return [
            'id' => $this->id,
            'title' => $titles,
            'content' => $contents,
            'released_at' => $this->released_at,
            'created_at' => $this->created_at,
            'read' => $read,
        ];
    }
}
