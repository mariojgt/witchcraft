<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class ConditionHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        try {
            // Check if using multiple conditions
            $useMultipleConditions = $this->getData($node, 'useMultipleConditions', false);

            if ($useMultipleConditions) {
                return $this->handleMultipleConditions($node, $variables);
            } else {
                return $this->handleSingleCondition($node, $variables);
            }
        } catch (\Exception $e) {
            return $this->error("Condition evaluation failed: " . $e->getMessage());
        }
    }

    /**
     * Handle single condition (simple mode)
     */
    private function handleSingleCondition(array $node, array $variables): array
    {
        $compareVariable = $this->getData($node, 'compareVariable', 'extractedValue');
        $actualValue = $this->getVariableValue($variables, $compareVariable);
        $expectedValue = $this->getData($node, 'expectedValue');
        $conditionType = $this->getData($node, 'conditionType', 'equals');

        // Replace variables in expected value
        $expectedValue = $this->replaceVariables($expectedValue, $variables);

        $result = $this->evaluateCondition($actualValue, $expectedValue, $conditionType, $node);

        return $this->success([
            'result' => $result,
            'actualValue' => $actualValue,
            'expectedValue' => $expectedValue,
            'extractedValue' => $actualValue,
        ], "Condition evaluated to: " . ($result ? 'true' : 'false'))
        + ['conditionResult' => $result];
    }

    /**
     * Handle multiple conditions
     */
    private function handleMultipleConditions(array $node, array $variables): array
    {
        $conditions = $this->getData($node, 'conditions', []);
        $logicalOperator = $this->getData($node, 'logicalOperator', 'AND');

        if (empty($conditions)) {
            return $this->error('No conditions defined');
        }

        $results = [];
        $details = [];

        // Evaluate each condition
        foreach ($conditions as $index => $condition) {
            $compareVariable = $condition['compareVariable'] ?? 'extractedValue';
            $actualValue = $this->getVariableValue($variables, $compareVariable);
            $expectedValue = $this->replaceVariables($condition['expectedValue'] ?? '', $variables);
            $conditionType = $condition['conditionType'] ?? 'equals';

            $result = $this->evaluateCondition($actualValue, $expectedValue, $conditionType, $condition);
            $results[] = $result;

            $details[] = [
                'variable' => $compareVariable,
                'actual' => $actualValue,
                'expected' => $expectedValue,
                'condition' => $conditionType,
                'result' => $result
            ];
        }

        // Apply logical operator
        $finalResult = $logicalOperator === 'OR'
            ? in_array(true, $results, true)
            : !in_array(false, $results, true);

        $passed = count(array_filter($results));
        $total = count($results);

        return $this->success([
            'result' => $finalResult,
            'extractedValue' => $finalResult,
            'details' => $details,
            'summary' => "{$passed}/{$total} conditions passed with {$logicalOperator} logic"
        ], "Multiple conditions: {$passed}/{$total} passed → " . ($finalResult ? 'TRUE' : 'FALSE'))
        + ['conditionResult' => $finalResult];
    }

    /**
     * Evaluate a single condition
     */
    private function evaluateCondition($actualValue, $expectedValue, string $conditionType, array $data = []): bool
    {
        return match ($conditionType) {
            'equals' => $actualValue == $expectedValue,
            'notEquals' => $actualValue != $expectedValue,
            'contains' => is_string($actualValue) && is_string($expectedValue)
                ? str_contains(strtolower($actualValue), strtolower($expectedValue)) : false,
            'greaterThan' => is_numeric($actualValue) && is_numeric($expectedValue)
                ? (float)$actualValue > (float)$expectedValue : false,
            'lessThan' => is_numeric($actualValue) && is_numeric($expectedValue)
                ? (float)$actualValue < (float)$expectedValue : false,
            'isEmpty' => empty($actualValue),
            'isNotEmpty' => !empty($actualValue),
            'changed' => $actualValue != ($data['previousValue'] ?? null),
            'inArray' => $this->checkInArray($actualValue, $expectedValue),
            default => false
        };
    }

    /**
     * Check if value is in array (comma-separated string or actual array)
     */
    private function checkInArray($value, $array): bool
    {
        if (is_string($array)) {
            $array = array_map('trim', explode(',', $array));
        }

        if (!is_array($array)) {
            return false;
        }

        return in_array($value, $array);
    }

    /**
     * Get variable value with dot notation support
     */
    private function getVariableValue(array $variables, string $path)
    {
        if (strpos($path, '.') !== false) {
            $parts = explode('.', $path);
            $current = $variables;

            foreach ($parts as $part) {
                if (is_array($current) && array_key_exists($part, $current)) {
                    $current = $current[$part];
                } elseif (is_object($current) && property_exists($current, $part)) {
                    $current = $current->$part;
                } else {
                    return null;
                }
            }

            return $current;
        }

        return $variables[$path] ?? null;
    }
}
