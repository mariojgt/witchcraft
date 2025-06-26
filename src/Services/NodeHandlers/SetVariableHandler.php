<?php
namespace Mariojgt\Witchcraft\Services\NodeHandlers;

use Illuminate\Support\Facades\Cache;

class SetVariableHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        try {
            $variableName = $this->getData($node, 'variableName');
            $valueType = $this->getData($node, 'valueType', 'string');
            $persistent = $this->getData($node, 'persistent', false);
            $cacheExpiry = $this->getData($node, 'cacheExpiry');

            // New functionality: Check if we should use extracted value
            $useExtractedValue = $this->getData($node, 'useExtractedValue', false);
            $sourceVariable = $this->getData($node, 'sourceVariable', 'extractedValue');

            if (empty($variableName)) {
                return $this->error('Variable name is required');
            }

            // Determine the value to use
            if ($useExtractedValue) {
                // Use value from the specified source variable
                if (!isset($variables[$sourceVariable])) {
                    return $this->error("Source variable '{$sourceVariable}' not found in workflow variables");
                }
                $rawValue = $variables[$sourceVariable];
                $message = "Variable '{$variableName}' set from source variable '{$sourceVariable}'";
            } else {
                // Use manual input value
                $variableValue = $this->getData($node, 'variableValue');
                if (empty($variableValue)) {
                    return $this->error('Variable value is required when not using extracted value');
                }
                // Replace variables in the value
                $rawValue = $this->replaceVariables($variableValue, $variables);
                $message = "Variable '{$variableName}' set from manual input";
            }

            // Convert value based on type
            $finalValue = $this->convertValueType($rawValue, $valueType);

            $output = [$variableName => $finalValue];

            // Handle persistent storage
            if ($persistent) {
                $cacheKey = "witchcraft_var_{$variableName}";
                if (!empty($cacheExpiry) && is_numeric($cacheExpiry)) {
                    Cache::put($cacheKey, $finalValue, now()->addMinutes((int)$cacheExpiry));
                    $message .= " in cache with {$cacheExpiry} minute expiry";
                } else {
                    Cache::forever($cacheKey, $finalValue);
                    $message .= " in persistent cache";
                }
            } else {
                $message .= " in workflow memory";
            }

            // APpend the 'extractedValue' to the output for edge routing
            if (isset($variables['extractedValue'])) {
                $output['extractedValue'] = $variables['extractedValue'];
                $message .= " with extracted value";
            }
            return $this->success($output, $message);
        } catch (\Exception $e) {
            return $this->error("Failed to set variable: " . $e->getMessage());
        }
    }

    private function convertValueType($value, $type)
    {
        return match ($type) {
            'number' => is_numeric($value) ? (float)$value : 0,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true) ?: $value,
            'array' => is_array($value) ? $value : explode(',', $value),
            default => (string)$value
        };
    }
}
