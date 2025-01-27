<?php

namespace Mariojgt\Witchcraft\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MediaFolderResource extends JsonResource
{
    public function toArray($request)
    {

        $created_at = '';
        if ($this->created_at) {
            $created_at = $this->created_at->diffForHumans();
        }
        $updated_at = '';
        if ($this->updated_at) {
            $updated_at = $this->updated_at->diffForHumans();
        }

        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'parent_id'  => $this->parent_id,
            'count'      => $this->children()->count(),
            'children'   => $this->children(),
            'parent'     => $this->parent(),
            'created_at' => $created_at,
            'updated_at' => $updated_at,
        ];
    }
}
