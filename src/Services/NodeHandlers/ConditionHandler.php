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
            // Basic equality and content checks
            'equals' => $actualValue == $expectedValue,
            'notEquals' => $actualValue != $expectedValue,
            'contains' => is_string($actualValue) && is_string($expectedValue)
                ? str_contains(strtolower($actualValue), strtolower($expectedValue)) : false,

            // Numeric comparisons - now includes all 4 operators
            'greaterThan' => $this->compareNumbers($actualValue, $expectedValue, '>'),
            'greaterThanOrEqual' => $this->compareNumbers($actualValue, $expectedValue, '>='),
            'lessThan' => $this->compareNumbers($actualValue, $expectedValue, '<'),
            'lessThanOrEqual' => $this->compareNumbers($actualValue, $expectedValue, '<='),

            // Empty/null checks
            'isEmpty' => empty($actualValue),
            'isNotEmpty' => !empty($actualValue),

            // Change detection
            'changed' => $actualValue != ($data['previousValue'] ?? null),

            // Array membership
            'inArray' => $this->checkInArray($actualValue, $expectedValue),

            // Generic string pattern matching - these are more flexible and reusable
            'stringContainsPattern' => $this->checkStringPattern($actualValue, $expectedValue),
            'multipleStringContains' => $this->checkMultipleStringContains($actualValue, $expectedValue),

            default => false
        };
    }

    /**
     * Compare two values numerically with proper type checking
     *
     * @param mixed $actual The actual value from the variable
     * @param mixed $expected The expected value to compare against
     * @param string $operator The comparison operator ('>', '>=', '<', '<=')
     * @return bool The result of the comparison
     */
    private function compareNumbers($actual, $expected, string $operator): bool
    {
        // First check if both values can be treated as numbers
        if (!is_numeric($actual) || !is_numeric($expected)) {
            return false;
        }

        // Convert to float for accurate comparison
        $actualNum = (float) $actual;
        $expectedNum = (float) $expected;

        return match ($operator) {
            '>' => $actualNum > $expectedNum,
            '>=' => $actualNum >= $expectedNum,
            '<' => $actualNum < $expectedNum,
            '<=' => $actualNum <= $expectedNum,
            default => false
        };
    }

    /**
     * Check if string contains a specific pattern (case-insensitive)
     * This is generic and can be used for any pattern matching needs
     */
    private function checkStringPattern($haystack, $pattern): bool
    {
        if (!is_string($haystack) || !is_string($pattern)) {
            return false;
        }

        return stripos($haystack, $pattern) !== false;
    }

    /**
     * Check if string contains multiple patterns (all must be present)
     * Format: "pattern1|pattern2|pattern3"
     * This is generic and can handle any combination of patterns
     */
    private function checkMultipleStringContains($haystack, $patterns): bool
    {
        if (!is_string($haystack) || !is_string($patterns)) {
            return false;
        }

        $patternList = array_map('trim', explode('|', $patterns));

        foreach ($patternList as $pattern) {
            if (stripos($haystack, $pattern) === false) {
                return false;
            }
        }

        return true;
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

    /**
     * Enhanced replace variables method that supports dot notation
     * Overrides the base class method with additional functionality
     */
    protected function replaceVariables($text, $variables)
    {
        if (empty($text)) {
            return $text;
        }

        return preg_replace_callback('/\{\{([^}]+)\}\}/', function ($matches) use ($variables) {
            $variablePath = trim($matches[1]);
            $value = $this->getVariableValue($variables, $variablePath);

            // Convert value to string representation
            if (is_null($value)) {
                return '';
            } elseif (is_bool($value)) {
                return $value ? 'true' : 'false';
            } elseif (is_array($value) || is_object($value)) {
                return json_encode($value);
            } else {
                return (string) $value;
            }
        }, $text);
    }

    /**
     * Validate condition configuration
     */
    public function validateCondition(array $condition): array
    {
        $errors = [];

        if (empty($condition['compareVariable'])) {
            $errors[] = 'Compare variable is required';
        }

        $conditionType = $condition['conditionType'] ?? '';
        $numericOperators = ['greaterThan', 'greaterThanOrEqual', 'lessThan', 'lessThanOrEqual'];

        if (in_array($conditionType, $numericOperators)) {
            $expectedValue = $condition['expectedValue'] ?? '';
            if (!empty($expectedValue) && !is_numeric($expectedValue)) {
                $errors[] = "Expected value must be numeric for '{$conditionType}' comparison";
            }
        }

        return $errors;
    }

    /**
     * Get human-readable description of a condition
     */
    public function describeCondition(array $condition): string
    {
        $variable = $condition['compareVariable'] ?? 'variable';
        $type = $condition['conditionType'] ?? 'equals';
        $expected = $condition['expectedValue'] ?? 'value';

        $descriptions = [
            'equals' => "{$variable} equals {$expected}",
            'notEquals' => "{$variable} does not equal {$expected}",
            'contains' => "{$variable} contains '{$expected}'",
            'greaterThan' => "{$variable} > {$expected}",
            'greaterThanOrEqual' => "{$variable} >= {$expected}",
            'lessThan' => "{$variable} < {$expected}",
            'lessThanOrEqual' => "{$variable} <= {$expected}",
            'isEmpty' => "{$variable} is empty",
            'isNotEmpty' => "{$variable} is not empty",
            'changed' => "{$variable} has changed",
            'inArray' => "{$variable} is in list [{$expected}]",
            'stringContainsPattern' => "{$variable} contains pattern '{$expected}'",
            'multipleStringContains' => "{$variable} contains all patterns: {$expected}"
        ];

        return $descriptions[$type] ?? "{$variable} {$type} {$expected}";
    }
}
