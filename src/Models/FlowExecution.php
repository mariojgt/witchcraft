<?php

namespace Mariojgt\Witchcraft\Models;

use Illuminate\Database\Eloquent\Model;

class FlowExecution extends Model
{
    protected $fillable = [
        'flow_diagram_id',
        'trigger_type',
        'status',
        'started_at',
        'completed_at',
        'execution_time',
        'variables',
        'error_message'
    ];

    protected $casts = [
        'variables' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function diagram()
    {
        return $this->belongsTo(FlowDiagram::class, 'flow_diagram_id');
    }

    public function nodeExecutions()
    {
        return $this->hasMany(FlowNodeExecution::class);
    }
}
