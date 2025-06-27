<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

use Illuminate\Support\Facades\Cache;

class GetVariableHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        try {
            $variableName = $this->getData($node, 'variableName');
            $outputVariable = $this->getData($node, 'outputVariable', 'retrievedValue');
            $defaultValue = $this->getData($node, 'defaultValue');
            $fromCache = $this->getData($node, 'fromCache', false);
            $failIfNotFound = $this->getData($node, 'failIfNotFound', false);
            $returnType = $this->getData($node, 'returnType', 'auto'); // New: specify return type

            if (empty($variableName)) {
                return $this->error('Variable name is required');
            }

            $value = null;
            $source = 'not found';

            // Try to get from cache first if specified
            if ($fromCache) {
                $cacheKey = "witchcraft_var_{$variableName}";
                $cachedValue = Cache::get($cacheKey);
                if ($cachedValue !== null) {
                    $value = $this->processRetrievedValue($cachedValue, $returnType);
                    $source = 'cache';
                }
            }

            // If not found in cache (or cache not requested), try workflow variables
            if ($value === null && isset($variables[$variableName])) {
                $value = $this->processRetrievedValue($variables[$variableName], $returnType);
                $source = 'workflow';
            }

            // Use default value if still not found
            if ($value === null) {
                if ($failIfNotFound) {
                    return $this->error("Variable '{$variableName}' not found and failIfNotFound is enabled");
                }
                $value = $this->processRetrievedValue($defaultValue, $returnType);
                $source = 'default';
            }

            $output = [
                $outputVariable => $value,
                'variableSource' => $source,
                'extractedValue' => $value
            ];

            $message = "Retrieved variable '{$variableName}' from {$source}";
            if ($source === 'default') {
                $message .= " (using default value)";
            }

            // Add type information to the message
            $valueType = $this->getValueType($value);
            $message .= " as {$valueType}";

            return $this->success($output, $message);
        } catch (\Exception $e) {
            return $this->error("Failed to get variable: " . $e->getMessage());
        }
    }

    /**
     * Process retrieved value based on return type
     */
    private function processRetrievedValue($value, $returnType)
    {
        if ($value === null) {
            return null;
        }

        return match ($returnType) {
            'string' => $this->ensureString($value),
            'number' => $this->ensureNumber($value),
            'boolean' => $this->ensureBoolean($value),
            'array' => $this->ensureArray($value),
            'object' => $this->ensureObject($value),
            'json' => $this->ensureJson($value),
            default => $this->autoDetectType($value) // 'auto' or any other value
        };
    }

    /**
     * Auto-detect and return appropriate type
     */
    private function autoDetectType($value)
    {
        // If it's already a proper PHP type, return as-is
        if (is_array($value) || is_object($value) || is_bool($value) || is_numeric($value)) {
            return $value;
        }

        // If it's a string, try to detect if it's JSON
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded; // Return as decoded JSON (array/object)
            }
        }

        return $value; // Return as-is
    }

    /**
     * Ensure value is returned as string
     */
    private function ensureString($value)
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
     * Ensure value is returned as number
     */
    private function ensureNumber($value)
    {
        if (is_numeric($value)) {
            return (float)$value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float)$value;
        }

        return 0;
    }

    /**
     * Ensure value is returned as boolean
     */
    private function ensureBoolean($value)
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_array($value)) {
            return !empty($value);
        }

        if (is_object($value)) {
            return !empty((array)$value);
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Ensure value is returned as array
     */
    private function ensureArray($value)
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return json_decode(json_encode($value), true);
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

        return [$value]; // Wrap single values in array
    }

    /**
     * Ensure value is returned as object
     */
    private function ensureObject($value)
    {
        if (is_object($value)) {
            return $value;
        }

        if (is_array($value)) {
            return (object)$value;
        }

        if (is_string($value)) {
            // Try to decode as JSON first
            $decoded = json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE && is_object($decoded)) {
                return $decoded;
            }
        }

        return (object)['value' => $value];
    }

    /**
     * Ensure value is returned as JSON string
     */
    private function ensureJson($value)
    {
        if (is_string($value)) {
            // Check if it's already valid JSON
            json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $value;
            }
        }

        // Convert to JSON
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json !== false ? $json : json_encode(['error' => 'Failed to encode to JSON']);
    }

    /**
     * Get human-readable type of value
     */
    private function getValueType($value)
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return 'boolean';
        }

        if (is_int($value)) {
            return 'integer';
        }

        if (is_float($value)) {
            return 'float';
        }

        if (is_string($value)) {
            // Check if it's JSON
            json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                return 'json string';
            }
            return 'string';
        }

        if (is_array($value)) {
            return 'array';
        }

        if (is_object($value)) {
            return 'object';
        }

        return 'unknown';
    }
}
