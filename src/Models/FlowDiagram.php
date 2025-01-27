<?php

namespace Mariojgt\Witchcraft\Models;


use Illuminate\Database\Eloquent\Model;

class FlowDiagram extends Model
{
    protected $fillable = [
        'name',
        'description',
        'nodes',
        'edges',
        'is_active'
    ];

    protected $casts = [
        'nodes' => 'array',
        'edges' => 'array',
        'is_active' => 'boolean',
        'node' => 'json',
        'edge' => 'json',
    ];
}
