<?php

namespace Mariojgt\Witchcraft\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Mariojgt\Witchcraft\Controllers\MediaController;

class MediaResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'user'        => $this->admin,
            'url'         => $this->renderFiles(true),
            'title'       => $this->title,
            'alt'         => $this->alt,
            'caption'     => $this->caption,
            'description' => $this->description,
            'ext'         => $this->extension,
            'media_size'  => $this->size(),
            'media_key'   => encrypt($this->id),
            'created_at'  => $this->created_at->diffForHumans(),
            'updated_at'  => $this->updated_at->diffForHumans(),
        ];
    }
}
