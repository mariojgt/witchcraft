<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class ConditionHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        $actualValue = $variables['extractedValue'] ?? null;
        $expectedValue = $this->getData($node, 'expectedValue');
        $conditionType = $this->getData($node, 'conditionType', 'equals');

        $result = match ($conditionType) {
            'equals' => $actualValue == $expectedValue,
            'notEquals' => $actualValue != $expectedValue,
            'contains' => is_string($actualValue) && is_string($expectedValue)
                ? str_contains($actualValue, $expectedValue) : false,
            'changed' => $actualValue != $this->getData($node, 'previousValue'),
            'greaterThan' => is_numeric($actualValue) && is_numeric($expectedValue)
                ? $actualValue > $expectedValue : false,
            'lessThan' => is_numeric($actualValue) && is_numeric($expectedValue)
                ? $actualValue < $expectedValue : false,
            'isEmpty' => empty($actualValue),
            'isNotEmpty' => !empty($actualValue),
            default => false
        };

        return $this->success([
            'result' => $result,
            'actualValue' => $actualValue,
            'expectedValue' => $expectedValue,
            'extractedValue' => $actualValue // Add for edge routing
        ], "Condition evaluated to: " . ($result ? 'true' : 'false'))
        + ['conditionResult' => $result]; // Add for edge routing
    }
}
