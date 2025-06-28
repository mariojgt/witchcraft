<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class TriggerHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        try {
            // ✨ NEW: Get variables array from node data
            $nodeVariables = $this->getData($node, 'variables', []);

            // Legacy support: Check for old single variable format
            if (empty($nodeVariables)) {
                return $this->handleLegacyFormat($node, $variables);
            }

            // Validate that we have at least one variable
            if (!is_array($nodeVariables) || empty($nodeVariables)) {
                return $this->error('No variables defined in trigger node');
            }

            $output = [];
            $processedVariables = [];
            $errors = [];

            // Process each variable
            foreach ($nodeVariables as $index => $variable) {
                $result = $this->processVariable($variable, $index, $variables);

                if ($result['success']) {
                    $output = array_merge($output, $result['data']);
                    $processedVariables[] = $result['variableName'];
                } else {
                    $errors[] = $result['error'];
                }
            }

            // If we have errors but also some successful variables, log warnings
            if (!empty($errors) && !empty($processedVariables)) {
                $warningMessage = "Some variables had issues: " . implode(', ', $errors);
                // You might want to log this warning
            }

            // If all variables failed, return error
            if (empty($processedVariables)) {
                return $this->error('All variables failed to process: ' . implode(', ', $errors));
            }

            // Set extractedValue to the first successfully processed variable
            if (!empty($output)) {
                $firstVariableName = $processedVariables[0];
                if (isset($output[$firstVariableName])) {
                    $output['extractedValue'] = $output[$firstVariableName];
                }
            }

            $message = $this->buildSuccessMessage($processedVariables, count($nodeVariables));

            return $this->success($output, $message);

        } catch (\Exception $e) {
            return $this->error("Failed to process trigger: " . $e->getMessage());
        }
    }

    /**
     * ✨ NEW: Process individual variable
     */
    private function processVariable(array $variable, int $index, array $workflowVariables): array
    {
        try {
            // Validate variable structure
            $variableName = $variable['name'] ?? '';
            $initialValue = $variable['initialValue'] ?? '';
            $type = $variable['type'] ?? 'string';

            if (empty($variableName)) {
                return [
                    'success' => false,
                    'error' => "Variable at index {$index} has no name"
                ];
            }

            // Check if there's an existing value in workflow variables
            $value = isset($workflowVariables[$variableName])
                ? $workflowVariables[$variableName]
                : $initialValue;

            // Convert value based on specified type
            $convertedValue = $this->convertValueByType($value, $type);

            return [
                'success' => true,
                'data' => [$variableName => $convertedValue],
                'variableName' => $variableName
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => "Variable '{$variableName}' processing failed: " . $e->getMessage()
            ];
        }
    }

    /**
     * ✨ NEW: Convert value based on specified type
     */
    private function convertValueByType($value, string $type)
    {
        switch ($type) {
            case 'number':
                return $this->convertToNumber($value);
            case 'boolean':
                return $this->convertToBoolean($value);
            case 'json':
                return $this->convertToJson($value);
            case 'array':
                return $this->convertToArray($value);
            default:
                return $this->convertToString($value);
        }
    }

    /**
     * Convert value to number
     */
    private function convertToNumber($value)
    {
        if (is_array($value) || is_object($value)) {
            return 0;
        }
        return is_numeric($value) ? (float)$value : 0;
    }

    /**
     * Convert value to boolean
     */
    private function convertToBoolean($value)
    {
        if (is_array($value)) {
            return !empty($value);
        }
        if (is_object($value)) {
            return !empty((array)$value);
        }
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
        }
        return (bool)$value;
    }

    /**
     * Convert value to JSON
     */
    private function convertToJson($value)
    {
        if (is_string($value)) {
            // Check if it's already valid JSON
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $value;
            }
        }

        // Convert to JSON
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json !== false ? $json : json_encode(['error' => 'Failed to encode to JSON']);
    }

    /**
     * Convert value to array
     */
    private function convertToArray($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array)$value;
        }

        if (is_string($value)) {
            // Try to decode as JSON first
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
            // Otherwise split by comma
            return array_map('trim', explode(',', $value));
        }

        return [$value];
    }

    /**
     * Convert value to string
     */
    private function convertToString($value)
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return '';
        }

        return (string)$value;
    }

    /**
     * ✨ NEW: Build success message
     */
    private function buildSuccessMessage(array $processedVariables, int $totalVariables): string
    {
        $processedCount = count($processedVariables);

        if ($processedCount === $totalVariables) {
            if ($processedCount === 1) {
                return "Trigger set variable: {$processedVariables[0]}";
            } else {
                return "Trigger set {$processedCount} variables: " . implode(', ', $processedVariables);
            }
        } else {
            return "Trigger set {$processedCount} of {$totalVariables} variables: " . implode(', ', $processedVariables);
        }
    }

    /**
     * ✨ NEW: Handle legacy single variable format for backward compatibility
     */
    private function handleLegacyFormat(array $node, array $variables): array
    {
        $outputKey = $this->getData($node, 'outputKey', 'output');
        $variableName = $this->getData($node, 'variableName', 'userStatus');
        $initialValue = $this->getData($node, 'initialValue');

        // Use existing workflow variable if available, otherwise use initial value
        $value = isset($variables[$variableName]) ? $variables[$variableName] : $initialValue;

        return $this->success([
            $outputKey => $value,
            'extractedValue' => $value
        ], "Trigger set {$outputKey} to {$value} (legacy format)");
    }

    /**
     * ✨ NEW: Get debug information about the trigger configuration
     */
    public function debugTriggerConfiguration(array $node): array
    {
        $nodeVariables = $this->getData($node, 'variables', []);

        $debug = [
            'total_variables' => count($nodeVariables),
            'variables' => [],
            'legacy_format' => empty($nodeVariables)
        ];

        if ($debug['legacy_format']) {
            $debug['legacy_data'] = [
                'variableName' => $this->getData($node, 'variableName'),
                'initialValue' => $this->getData($node, 'initialValue'),
                'outputKey' => $this->getData($node, 'outputKey', 'output')
            ];
        } else {
            foreach ($nodeVariables as $index => $variable) {
                $debug['variables'][] = [
                    'index' => $index,
                    'name' => $variable['name'] ?? 'unnamed',
                    'initialValue' => $variable['initialValue'] ?? '',
                    'type' => $variable['type'] ?? 'string',
                    'description' => $variable['description'] ?? '',
                    'valid' => !empty($variable['name'])
                ];
            }
        }

        return $debug;
    }

    /**
     * ✨ NEW: Validate trigger configuration
     */
    public function validateTriggerConfiguration(array $node): array
    {
        $nodeVariables = $this->getData($node, 'variables', []);

        if (empty($nodeVariables)) {
            // Check legacy format
            $variableName = $this->getData($node, 'variableName');
            if (empty($variableName)) {
                return [
                    'valid' => false,
                    'errors' => ['No variables defined and no legacy variable name provided']
                ];
            }
            return [
                'valid' => true,
                'warnings' => ['Using legacy format - consider updating to new multiple variables format']
            ];
        }

        $errors = [];
        $warnings = [];
        $validVariables = 0;

        foreach ($nodeVariables as $index => $variable) {
            $variableName = $variable['name'] ?? '';

            if (empty($variableName)) {
                $errors[] = "Variable at index {$index} has no name";
                continue;
            }

            $validVariables++;

            // Check for valid type
            $allowedTypes = ['string', 'number', 'boolean', 'json', 'array'];
            $type = $variable['type'] ?? 'string';
            if (!in_array($type, $allowedTypes)) {
                $warnings[] = "Variable '{$variableName}' has invalid type '{$type}', will default to 'string'";
            }

            // Check for initial value
            if (!isset($variable['initialValue']) || $variable['initialValue'] === '') {
                $warnings[] = "Variable '{$variableName}' has no initial value";
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'valid_variables' => $validVariables,
            'total_variables' => count($nodeVariables)
        ];
    }
}
