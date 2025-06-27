<?php

use Mariojgt\Witchcraft\Models\FlowDiagram;
use Mariojgt\Witchcraft\Services\FlowExecutor;

if (!function_exists('witchcraft_trigger')) {
    /**
     * Trigger a workflow by its trigger code
     *
     * @param string $triggerCode The trigger code of the workflow
     * @param array $data Initial data to pass to the workflow
     * @return mixed The result of the workflow execution
     * @throws Exception If workflow not found or execution fails
     */
    function witchcraft_trigger(string $triggerCode, array $data = [])
    {
        // Find the workflow by trigger code (latest version only)
        $flowDiagram = FlowDiagram::findByTriggerCode($triggerCode);

        if (!$flowDiagram) {
            throw new Exception("Workflow with trigger code '{$triggerCode}' not found");
        }

        // Execute the workflow
        $executor = new FlowExecutor($flowDiagram);
        return $executor->run($data);
    }
}

if (!function_exists('witchcraft_execute')) {
    /**
     * Alternative alias for witchcraft_trigger
     *
     * @param string $triggerCode The trigger code of the workflow
     * @param array $data Initial data to pass to the workflow
     * @return mixed The result of the workflow execution
     */
    function witchcraft_execute(string $triggerCode, array $data = [])
    {
        return witchcraft_trigger($triggerCode, $data);
    }
}

if (!function_exists('witchcraft_run')) {
    /**
     * Another alias for witchcraft_trigger
     *
     * @param string $triggerCode The trigger code of the workflow
     * @param array $data Initial data to pass to the workflow
     * @return mixed The result of the workflow execution
     */
    function witchcraft_run(string $triggerCode, array $data = [])
    {
        return witchcraft_trigger($triggerCode, $data);
    }
}

if (!function_exists('witchcraft_safe_trigger')) {
    /**
     * Safely trigger a workflow with error handling
     *
     * @param string $triggerCode The trigger code of the workflow
     * @param array $data Initial data to pass to the workflow
     * @param mixed $defaultReturn Default value to return on error
     * @return array Returns ['success' => bool, 'result' => mixed, 'error' => string|null]
     */
    function witchcraft_safe_trigger(string $triggerCode, array $data = [], $defaultReturn = null)
    {
        try {
            $result = witchcraft_trigger($triggerCode, $data);
            return [
                'success' => true,
                'result' => $result,
                'error' => null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'result' => $defaultReturn,
                'error' => $e->getMessage()
            ];
        }
    }
}

if (!function_exists('witchcraft_exists')) {
    /**
     * Check if a workflow exists by trigger code
     *
     * @param string $triggerCode The trigger code to check
     * @return bool True if workflow exists, false otherwise
     */
    function witchcraft_exists(string $triggerCode): bool
    {
        return FlowDiagram::findByTriggerCode($triggerCode) !== null;
    }
}

if (!function_exists('witchcraft_info')) {
    /**
     * Get information about a workflow
     *
     * @param string $triggerCode The trigger code of the workflow
     * @return array|null Workflow information or null if not found
     */
    function witchcraft_info(string $triggerCode): ?array
    {
        $flowDiagram = FlowDiagram::findByTriggerCode($triggerCode);

        if (!$flowDiagram) {
            return null;
        }

        return [
            'id' => $flowDiagram->id,
            'name' => $flowDiagram->name,
            'description' => $flowDiagram->description,
            'category' => $flowDiagram->category,
            'trigger_code' => $flowDiagram->trigger_code,
            'version' => $flowDiagram->version,
            'node_count' => $flowDiagram->getNodeCount(),
            'edge_count' => $flowDiagram->getEdgeCount(),
            'is_deletable' => $flowDiagram->is_deletable,
            'created_at' => $flowDiagram->created_at,
            'updated_at' => $flowDiagram->updated_at,
        ];
    }
}
