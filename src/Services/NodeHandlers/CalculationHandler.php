<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class CalculationHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        try {
            $expression = $this->getData($node, 'expression', '');
            $outputVariable = $this->getData($node, 'outputVariable', 'calculatedValue');

            if (empty($expression)) {
                return $this->error('No calculation expression provided');
            }

            // Replace variables in the expression
            $processedExpression = $this->replaceVariablesInExpression($expression, $variables);

            // Evaluate the mathematical expression safely
            $result = $this->evaluateExpression($processedExpression);

            return $this->success([
                'result' => $result,
                'expression' => $expression,
                'processedExpression' => $processedExpression,
                'outputVariable' => $outputVariable,
                $outputVariable => $result,
                'extractedValue' => $result,
            ], "Calculation result: {$result}");
        } catch (\Exception $e) {
            return $this->error("Calculation failed: " . $e->getMessage());
        }
    }

    /**
     * Replace variables in mathematical expression
     */
    private function replaceVariablesInExpression(string $expression, array $variables): string
    {
        // Pattern to match variables: {{variableName}} or {{object.property}}
        $pattern = '/\{\{([^}]+)\}\}/';

        return preg_replace_callback($pattern, function ($matches) use ($variables) {
            $variablePath = trim($matches[1]);
            $value = $this->getVariableValue($variables, $variablePath);

            // Handle different value types
            if (is_null($value)) {
                return '0'; // Default to 0 for null values
            }

            if (is_bool($value)) {
                return $value ? '1' : '0';
            }

            if (is_numeric($value)) {
                return (string) $value;
            }

            if (is_string($value) && is_numeric($value)) {
                return $value;
            }

            // For non-numeric strings, return 0
            return '0';
        }, $expression);
    }

    /**
     * Safely evaluate mathematical expression
     */
    private function evaluateExpression(string $expression): float
    {
        // Remove whitespace
        $expression = preg_replace('/\s+/', '', $expression);

        // Validate expression contains only allowed characters
        if (!preg_match('/^[0-9+\-*\/.()?\:]+$/', $expression)) {
            throw new \Exception('Invalid characters in expression. Only numbers, +, -, *, /, ., (, ), ?, : are allowed');
        }

        // Handle ternary operators (condition ? true_value : false_value)
        $expression = $this->processTernaryOperators($expression);

        // Use a safe mathematical expression evaluator
        return $this->safeMathEval($expression);
    }

    /**
     * Process ternary operators in the expression
     */
    private function processTernaryOperators(string $expression): string
    {
        // Simple ternary pattern: condition ? true_value : false_value
        $pattern = '/([0-9.]+)\s*\?\s*([0-9.]+)\s*:\s*([0-9.]+)/';

        return preg_replace_callback($pattern, function ($matches) {
            $condition = (float) $matches[1];
            $trueValue = (float) $matches[2];
            $falseValue = (float) $matches[3];

            return $condition ? $trueValue : $falseValue;
        }, $expression);
    }

    /**
     * Safe mathematical expression evaluator
     */
    private function safeMathEval(string $expression): float
    {
        // Remove any remaining whitespace
        $expression = str_replace(' ', '', $expression);

        // Validate the expression is safe (only numbers and basic math operators)
        if (!preg_match('/^[0-9+\-*\/.()]+$/', $expression)) {
            throw new \Exception('Expression contains invalid characters');
        }

        // Use eval() with strict validation (consider using a proper math parser library in production)
        $result = eval("return $expression;");

        if ($result === false || !is_numeric($result)) {
            throw new \Exception('Invalid mathematical expression');
        }

        return (float) $result;
    }

    /**
     * Get variable value with dot notation support (same as ConditionHandler)
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
