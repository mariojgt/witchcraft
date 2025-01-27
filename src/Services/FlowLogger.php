<?php

namespace Mariojgt\Witchcraft\Services;

use Illuminate\Support\Facades\Log;

use Mariojgt\Witchcraft\Models\FlowDiagram;
use Mariojgt\Witchcraft\Models\FlowExecution;
use Mariojgt\Witchcraft\Models\FlowNodeExecution;

class FlowLogger
{
    protected $execution;
    protected $nodeExecution;
    protected $startTime;

    public function startExecution($diagramId, $triggerType, $variables)
    {
        $this->startTime = microtime(true);

        $this->execution = FlowExecution::create([
            'flow_diagram_id' => $diagramId,
            'trigger_type' => $triggerType,
            'status' => 'started',
            'started_at' => now(),
            'variables' => $variables
        ]);

        Log::channel('flow-diagrams')->info("Starting flow execution", [
            'execution_id' => $this->execution->id,
            'diagram_id' => $diagramId,
            'trigger_type' => $triggerType
        ]);

        return $this->execution;
    }

    public function startNode($nodeId, $nodeType, $inputData)
    {
        $this->nodeExecution = FlowNodeExecution::create([
            'flow_execution_id' => $this->execution->id,
            'node_id' => $nodeId,
            'node_type' => $nodeType,
            'status' => 'started',
            'started_at' => now(),
            'input_data' => $inputData
        ]);

        Log::channel('flow-diagrams')->info("Processing node", [
            'execution_id' => $this->execution->id,
            'node_id' => $nodeId,
            'node_type' => $nodeType
        ]);
    }

    public function completeNode($outputData = null)
    {
        if (!$this->nodeExecution) {
            return;
        }

        $endTime = microtime(true);
        $executionTime = $endTime - microtime(true);

        $this->nodeExecution->update([
            'status' => 'completed',
            'completed_at' => now(),
            'execution_time' => $executionTime,
            'output_data' => $outputData
        ]);

        Log::channel('flow-diagrams')->info("Node completed", [
            'execution_id' => $this->execution->id,
            'node_id' => $this->nodeExecution->node_id,
            'execution_time' => $executionTime
        ]);
    }

    public function failNode($error)
    {
        if (!$this->nodeExecution) {
            return;
        }

        $endTime = microtime(true);
        $executionTime = $endTime - microtime(true);

        $this->nodeExecution->update([
            'status' => 'failed',
            'completed_at' => now(),
            'execution_time' => $executionTime,
            'error_message' => $error
        ]);

        Log::channel('flow-diagrams')->error("Node failed", [
            'execution_id' => $this->execution->id,
            'node_id' => $this->nodeExecution->node_id,
            'error' => $error
        ]);
    }

    public function completeExecution()
    {
        if (!$this->execution) {
            return;
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $this->startTime;

        $this->execution->update([
            'status' => 'completed',
            'completed_at' => now(),
            'execution_time' => $executionTime
        ]);

        Log::channel('flow-diagrams')->info("Flow execution completed", [
            'execution_id' => $this->execution->id,
            'execution_time' => $executionTime
        ]);
    }

    public function failExecution($error)
    {
        if (!$this->execution) {
            return;
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $this->startTime;

        $this->execution->update([
            'status' => 'failed',
            'completed_at' => now(),
            'execution_time' => $executionTime,
            'error_message' => $error
        ]);

        Log::channel('flow-diagrams')->error("Flow execution failed", [
            'execution_id' => $this->execution->id,
            'error' => $error,
            'execution_time' => $executionTime
        ]);
    }
}
