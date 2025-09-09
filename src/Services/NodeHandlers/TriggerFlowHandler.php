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
            // ✨ NEW: Support both trigger_code and flowId for backward compatibility
            $flowId = $this->getData($node, 'flowId');
            $triggerCode = $this->getData($node, 'triggerCode');
            $selectedFlow = $this->getData($node, 'selectedFlow');
            $inputVariable = $this->getData($node, 'inputVariable');
            $targetVariableName = $this->getData($node, 'targetVariableName', 'dataInput');
            $waitForCompletion = $this->getData($node, 'waitForCompletion', true);
            $resultVariable = $this->getData($node, 'resultVariable', 'flowResult');
            $async = $this->getData($node, 'async', false);

            // ✨ NEW: Priority order for finding flow: trigger_code > selectedFlow > flowId
            $targetTriggerCode = null;
            $targetFlowId = null;
            $flowDiagram = null;

            // 1. First try trigger_code (preferred method - always gets latest version)
            if (!empty($triggerCode)) {
                $targetTriggerCode = $triggerCode;
                $flowDiagram = FlowDiagram::findByTriggerCode($targetTriggerCode);
                if (!$flowDiagram) {
                    return $this->error("Flow with trigger code '{$targetTriggerCode}' not found");
                }
            } elseif ($selectedFlow && isset($selectedFlow['trigger_code'])) {
                // 2. Then try selectedFlow trigger_code
                $targetTriggerCode = $selectedFlow['trigger_code'];
                $flowDiagram = FlowDiagram::findByTriggerCode($targetTriggerCode);
                if (!$flowDiagram) {
                    return $this->error("Flow with trigger code '{$targetTriggerCode}' not found");
                }
            } elseif ($selectedFlow && isset($selectedFlow['id'])) {
                // 3. Then try selectedFlow id
                $targetFlowId = $selectedFlow['id'];
                $flowDiagram = FlowDiagram::find($targetFlowId);
                if (!$flowDiagram) {
                    return $this->error("Flow with ID {$targetFlowId} not found");
                }
            } elseif (!empty($flowId)) {
                // 4. Finally try flowId (legacy support)
                $targetFlowId = $flowId;
                $flowDiagram = FlowDiagram::find($targetFlowId);
                if (!$flowDiagram) {
                    return $this->error("Flow with ID {$targetFlowId} not found");
                }
            }

            if (!$flowDiagram) {
                return $this->error('Flow identifier is required (trigger_code, selectedFlow, or flowId)');
            }

            // Prepare initial data - simple variable mapping
            $initialData = [];
            if (!empty($inputVariable) && isset($variables[$inputVariable])) {
                $initialData[$targetVariableName] = $variables[$inputVariable];
                $initialData = $variables[$inputVariable];
            }

            // Handle async execution
            if ($async) {
                $this->executeFlowAsync($flowDiagram, $initialData);
                $identificationMethod = $targetTriggerCode
                    ? "trigger code '{$targetTriggerCode}'"
                    : "ID {$targetFlowId}";
                return $this->success(
                    ['asyncJobId' => uniqid('flow_', true)],
                    "Flow '{$flowDiagram->name}' (via {$identificationMethod}) queued for async execution"
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

            // ✨ Enhanced success message with identification method
            $identificationMethod = $targetTriggerCode
                ? "trigger code '{$targetTriggerCode}'"
                : "ID {$targetFlowId}";
            $message = "Successfully executed flow '{$flowDiagram->name}' (via {$identificationMethod})";
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

    /**
     * ✨ NEW: Get flow identification info for debugging
     */
    public function getFlowIdentificationInfo(array $node): array
    {
        $flowId = $this->getData($node, 'flowId');
        $triggerCode = $this->getData($node, 'triggerCode');
        $selectedFlow = $this->getData($node, 'selectedFlow');

        $info = [
            'has_trigger_code' => !empty($triggerCode),
            'trigger_code' => $triggerCode,
            'has_flow_id' => !empty($flowId),
            'flow_id' => $flowId,
            'has_selected_flow' => !empty($selectedFlow),
            'selected_flow' => $selectedFlow,
            'priority_order' => [
                '1st_priority' => 'triggerCode (gets latest version)',
                '2nd_priority' => 'selectedFlow.trigger_code (gets latest version)',
                '3rd_priority' => 'selectedFlow.id (specific version)',
                '4th_priority' => 'flowId (specific version, legacy)'
            ]
        ];

        return $info;
    }

    /**
     * ✨ NEW: Validate flow configuration
     */
    public function validateFlowConfiguration(array $node): array
    {
        $triggerCode = $this->getData($node, 'triggerCode');
        $flowId = $this->getData($node, 'flowId');
        $selectedFlow = $this->getData($node, 'selectedFlow');

        $errors = [];
        $warnings = [];
        $recommendations = [];

        // Check if any flow identifier exists
        if (empty($triggerCode) && empty($flowId) && empty($selectedFlow)) {
            $errors[] = 'No flow identifier provided (triggerCode, flowId, or selectedFlow required)';
        }

        // Check if flow exists
        if (!empty($triggerCode)) {
            $flow = FlowDiagram::findByTriggerCode($triggerCode);
            if (!$flow) {
                $errors[] = "Flow with trigger code '{$triggerCode}' not found";
            } else {
                $recommendations[] = "Using trigger code ensures latest version (v{$flow->version})";
            }
        }

        if (!empty($flowId)) {
            $flow = FlowDiagram::find($flowId);
            if (!$flow) {
                $errors[] = "Flow with ID {$flowId} not found";
            } else {
                if (!$flow->is_latest_version) {
                    $warnings[] = "Using specific ID points to version {$flow->version}, not latest";
                    $recommendations[] = "Consider using trigger_code for automatic latest version";
                }
            }
        }

        if ($selectedFlow) {
            if (isset($selectedFlow['trigger_code'])) {
                $flow = FlowDiagram::findByTriggerCode($selectedFlow['trigger_code']);
                if (!$flow) {
                    $errors[] = "Selected flow with trigger code '{$selectedFlow['trigger_code']}' not found";
                }
            } elseif (isset($selectedFlow['id'])) {
                $flow = FlowDiagram::find($selectedFlow['id']);
                if (!$flow) {
                    $errors[] = "Selected flow with ID {$selectedFlow['id']} not found";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'recommendations' => $recommendations
        ];
    }
}
