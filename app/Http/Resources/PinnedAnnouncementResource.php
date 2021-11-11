<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PinnedAnnouncementResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
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
            'read' => false,
        ];
    }
}
