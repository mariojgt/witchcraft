<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class CombineVariablesHandler extends BaseNodeHandler
{
    /**
     * Handle the Combine Variables node execution
     *
     * Combines multiple variables from the workflow into either an array or object
     * and returns the result with a custom variable name.
     *
     * @param array $node The node configuration
     * @param array $variables The current workflow variables
     * @return array The execution result
     */
    public function handle(array $node, array $variables): array
    {
        try {
            // Get node configuration
            $variablesToCombine = $this->getData($node, 'variablesToCombine', []);
            $outputType = $this->getData($node, 'outputType', 'array');
            $returnVariableName = $this->getData($node, 'returnVariableName', 'combinedResult');

            // Validate return variable name
            if (!$this->isValidVariableName($returnVariableName)) {
                return $this->error("Invalid return variable name '{$returnVariableName}'. Must be a valid identifier.");
            }

            // Validate that we have variables to combine
            if (empty($variablesToCombine)) {
                return $this->error('No variables specified for combination');
            }

            // Validate output type
            if (!in_array($outputType, ['array', 'object'])) {
                return $this->error("Invalid output type '{$outputType}'. Must be 'array' or 'object'.");
            }

            // Process variables and combine them
            $combinedResult = $this->combineVariables($variablesToCombine, $variables, $outputType);

            // Count successful and failed extractions for reporting
            $successCount = 0;
            $failedCount = 0;
            $extractedVariables = [];

            foreach ($variablesToCombine as $variableConfig) {
                $source = $variableConfig['source'] ?? '';
                if (empty($source)) continue;

                $extractedValue = $this->extractVariableValue($source, $variables);

                if ($extractedValue !== null) {
                    $successCount++;
                    $extractedVariables[] = $source;
                } else {
                    $failedCount++;
                }
            }

            // Prepare output
            $output = [
                'extractedValue' => $combinedResult,
                $returnVariableName => $combinedResult,
                'combinedType' => $outputType,
                'combinedCount' => count($variablesToCombine),
                'successfulExtractions' => $successCount,
                'failedExtractions' => $failedCount,
                'extractedVariables' => $extractedVariables
            ];

            $message = "Successfully combined {$successCount} variables into {$outputType} as '{$returnVariableName}'";

            if ($failedCount > 0) {
                $message .= " ({$failedCount} variables not found)";
            }

            return $this->success($output, $message);

        } catch (\Exception $e) {
            return $this->error("Failed to combine variables: " . $e->getMessage());
        }
    }

    /**
     * Combine variables based on the specified configuration
     *
     * @param array $variablesToCombine Array of variable configurations
     * @param array $variables Available workflow variables
     * @param string $outputType 'array' or 'object'
     * @return array|object The combined result
     */
    private function combineVariables(array $variablesToCombine, array $variables, string $outputType)
    {
        if ($outputType === 'array') {
            return $this->combineAsArray($variablesToCombine, $variables);
        } else {
            return $this->combineAsObject($variablesToCombine, $variables);
        }
    }

    /**
     * Combine variables into an array
     *
     * @param array $variablesToCombine Variable configurations
     * @param array $variables Available workflow variables
     * @return array Combined array result
     */
    private function combineAsArray(array $variablesToCombine, array $variables): array
    {
        $result = [];

        foreach ($variablesToCombine as $variableConfig) {
            $source = $variableConfig['source'] ?? '';

            if (empty($source)) {
                $result[] = null;
                continue;
            }

            $extractedValue = $this->extractVariableValue($source, $variables);
            $result[] = $extractedValue;
        }

        return $result;
    }

    /**
     * Combine variables into an object
     *
     * @param array $variablesToCombine Variable configurations
     * @param array $variables Available workflow variables
     * @return array Combined object result (as associative array)
     */
    private function combineAsObject(array $variablesToCombine, array $variables): array
    {
        $result = [];

        foreach ($variablesToCombine as $variableConfig) {
            $source = $variableConfig['source'] ?? '';
            $key = $variableConfig['key'] ?? $source;

            if (empty($source) || empty($key)) {
                continue;
            }

            $extractedValue = $this->extractVariableValue($source, $variables);
            $result[$key] = $extractedValue;
        }

        return $result;
    }

    /**
     * Extract value from workflow variables using dot notation
     *
     * @param string $source Variable name or path (e.g., 'user.email' or 'data.0.name')
     * @param array $variables Available workflow variables
     * @return mixed The extracted value or null if not found
     */
    private function extractVariableValue(string $source, array $variables)
    {
        if (empty($source)) {
            return null;
        }

        // Handle simple variable name (no dot notation)
        if (!str_contains($source, '.')) {
            return $variables[$source] ?? null;
        }

        // Handle dot notation path
        $keys = explode('.', $source);
        $current = $variables;

        foreach ($keys as $key) {
            if ($current === null) {
                return null;
            }

            // Handle array indices
            if (is_numeric($key)) {
                $index = (int) $key;
                if (is_array($current) && isset($current[$index])) {
                    $current = $current[$index];
                } else {
                    return null;
                }
            }
            // Handle object/array properties
            elseif (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            }
            // Handle object properties (if it's a stdClass or similar)
            elseif (is_object($current) && property_exists($current, $key)) {
                $current = $current->$key;
            }
            // Handle object properties using array access for objects that support it
            elseif (is_object($current) && method_exists($current, 'offsetExists') && $current->offsetExists($key)) {
                $current = $current[$key];
            }
            else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Validate if a variable name is a valid PHP identifier
     *
     * @param string $name The variable name to validate
     * @return bool True if valid, false otherwise
     */
    private function isValidVariableName(string $name): bool
    {
        return !empty($name) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name);
    }

    /**
     * Get debug information about the combination process
     * This method can be called during development to understand the combination
     *
     * @param array $variablesToCombine Variable configurations
     * @param array $variables Available workflow variables
     * @param string $outputType Output type ('array' or 'object')
     * @return array Debug information
     */
    public function debugCombination(array $variablesToCombine, array $variables, string $outputType): array
    {
        $debug = [
            'configuration' => [
                'variables_to_combine' => count($variablesToCombine),
                'output_type' => $outputType,
                'available_variables' => array_keys($variables)
            ],
            'extraction_results' => [],
            'final_structure' => null
        ];

        // Test each variable extraction
        foreach ($variablesToCombine as $index => $variableConfig) {
            $source = $variableConfig['source'] ?? '';
            $key = $variableConfig['key'] ?? $source;

            $extractedValue = $this->extractVariableValue($source, $variables);

            $debug['extraction_results'][] = [
                'index' => $index,
                'source' => $source,
                'key' => $key,
                'found' => $extractedValue !== null,
                'value_type' => $extractedValue !== null ? gettype($extractedValue) : 'null',
                'value_preview' => $this->getValuePreview($extractedValue)
            ];
        }

        // Show final combined structure
        $debug['final_structure'] = $this->combineVariables($variablesToCombine, $variables, $outputType);

        return $debug;
    }

    /**
     * Get a preview of a value for debugging
     *
     * @param mixed $value The value to preview
     * @return string Preview string
     */
    private function getValuePreview($value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_string($value)) {
            return strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value;
        }

        if (is_array($value)) {
            return 'array(' . count($value) . ' items)';
        }

        if (is_object($value)) {
            return get_class($value) . ' object';
        }

        return (string) $value;
    }

    /**
     * Validate the combination configuration before execution
     *
     * @param array $variablesToCombine Variable configurations
     * @param string $outputType Output type
     * @return array Validation result with errors if any
     */
    public function validateConfiguration(array $variablesToCombine, string $outputType): array
    {
        $errors = [];
        $warnings = [];

        // Check if we have variables to combine
        if (empty($variablesToCombine)) {
            $errors[] = 'No variables specified for combination';
        }

        // Validate output type
        if (!in_array($outputType, ['array', 'object'])) {
            $errors[] = "Invalid output type '{$outputType}'. Must be 'array' or 'object'.";
        }

        // Check each variable configuration
        foreach ($variablesToCombine as $index => $variableConfig) {
            $source = $variableConfig['source'] ?? '';
            $key = $variableConfig['key'] ?? '';

            if (empty($source)) {
                $warnings[] = "Variable at index {$index} has empty source";
            }

            if ($outputType === 'object' && empty($key)) {
                $warnings[] = "Variable at index {$index} has empty key for object output";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }
}
