<?php

namespace Mariojgt\Witchcraft\Models;

use Illuminate\Database\Eloquent\Model;

class FlowNodeExecution extends Model
{
    protected $fillable = [
        'flow_execution_id',
        'node_id',
        'node_type',
        'status',
        'started_at',
        'completed_at',
        'execution_time',
        'input_data',
        'output_data',
        'error_message'
    ];

    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function execution()
    {
        return $this->belongsTo(FlowExecution::class, 'flow_execution_id');
    }
}
