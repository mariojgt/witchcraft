<?php

namespace Mariojgt\Witchcraft\Models;

use Illuminate\Database\Eloquent\Model;

class FlowSimulationRun extends Model
{
    protected $fillable = [
        'flow_diagram_id',
        'execution_log',
        'final_variables',
        'status',
        'error_message',
        'total_nodes',
        'completed_nodes',
        'started_at',
        'completed_at',
        'duration_ms'
    ];

    protected $casts = [
        'execution_log' => 'array',
        'final_variables' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the flow diagram this run belongs to
     */
    public function flowDiagram()
    {
        return $this->belongsTo(FlowDiagram::class);
    }

    /**
     * Get the latest simulation runs for a diagram
     */
    public static function getLatestRuns($flowDiagramId, $limit = 10)
    {
        return static::where('flow_diagram_id', $flowDiagramId)
                    ->orderBy('created_at', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Get success rate for a diagram
     */
    public static function getSuccessRate($flowDiagramId)
    {
        $total = static::where('flow_diagram_id', $flowDiagramId)->count();
        if ($total === 0) {
            return 0;
        }

        $successful = static::where('flow_diagram_id', $flowDiagramId)
                           ->where('status', 'success')
                           ->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get average execution time for a diagram
     */
    public static function getAverageExecutionTime($flowDiagramId)
    {
        return static::where('flow_diagram_id', $flowDiagramId)
                    ->whereNotNull('duration_ms')
                    ->avg('duration_ms');
    }
}
