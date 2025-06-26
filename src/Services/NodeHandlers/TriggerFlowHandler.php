<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

use Mariojgt\Witchcraft\Models\FlowDiagram;
use Mariojgt\Witchcraft\Services\FlowExecutor;
use Illuminate\Support\Facades\Queue;

class TriggerFlowHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        try {
            $flowId = $this->getData($node, 'flowId');
            $selectedFlow = $this->getData($node, 'selectedFlow');
            $inputVariable = $this->getData($node, 'inputVariable');
            $targetVariableName = $this->getData($node, 'targetVariableName', 'dataInput');
            $waitForCompletion = $this->getData($node, 'waitForCompletion', true);
            $resultVariable = $this->getData($node, 'resultVariable', 'flowResult');
            $async = $this->getData($node, 'async', false);

            // Determine flow ID from selection or manual input
            $targetFlowId = $flowId;
            if ($selectedFlow && isset($selectedFlow['id'])) {
                $targetFlowId = $selectedFlow['id'];
            }

            if (empty($targetFlowId)) {
                return $this->error('Flow ID is required');
            }

            // Load target flow
            $flowDiagram = FlowDiagram::find($targetFlowId);
            if (!$flowDiagram) {
                return $this->error("Flow with ID {$targetFlowId} not found");
            }

            // Prepare initial data - simple variable mapping
            $initialData = [];
            if (!empty($inputVariable) && isset($variables[$inputVariable])) {
                $initialData[$targetVariableName] = $variables[$inputVariable];
            }

            // Handle async execution
            if ($async) {
                $this->executeFlowAsync($flowDiagram, $initialData);
                return $this->success(
                    ['asyncJobId' => uniqid('flow_', true)],
                    "Flow '{$flowDiagram->name}' queued for async execution"
                );
            }

            // Execute flow synchronously
            $executor = new FlowExecutor($flowDiagram);
            $result = $executor->run($initialData);

            $output = [];

            if ($waitForCompletion) {
                $output[$resultVariable] = $result;
                $output['flowExecutionSuccess'] = isset($result['variables']);
                $output['flowExecutionLogs'] = $result['executionLog'] ?? [];
            }

            // Merge any variables returned by the executed flow
            if (isset($result['variables']) && is_array($result['variables'])) {
                $output = array_merge($output, $result['variables']);
            }

            $message = "Successfully executed flow '{$flowDiagram->name}'";
            if (!empty($inputVariable)) {
                $message .= " with variable '{$inputVariable}' as '{$targetVariableName}'";
            }
            if (!$waitForCompletion) {
                $message .= " (fire and forget)";
            }

            return $this->success($output, $message);
        } catch (\Exception $e) {
            return $this->error("Flow execution failed: " . $e->getMessage());
        }
    }

    /**
     * Execute flow asynchronously using Laravel's queue system
     */
    private function executeFlowAsync(FlowDiagram $flowDiagram, array $initialData): void
    {
        // If you have queue system set up, use it:
        // Queue::push(new ExecuteFlowJob($flowDiagram, $initialData));

        // For now, we'll use a simple background process
        // In production, you should use proper queue system
        $this->executeFlowInBackground($flowDiagram, $initialData);
    }

    /**
     * Simple background execution (use proper queues in production)
     */
    private function executeFlowInBackground(FlowDiagram $flowDiagram, array $initialData): void
    {
        // Create a simple background job
        $command = sprintf(
            'php artisan witchcraft:execute-flow %d %s > /dev/null 2>&1 &',
            $flowDiagram->id,
            escapeshellarg(json_encode($initialData))
        );

        exec($command);
    }
}
