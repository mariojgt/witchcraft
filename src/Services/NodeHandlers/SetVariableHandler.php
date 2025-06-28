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

            // Enhanced functionality: Check if we should use extracted value
            $useExtractedValue = $this->getData($node, 'useExtractedValue', false);
            $sourceVariable = $this->getData($node, 'sourceVariable', 'extractedValue');
            $extractPath = $this->getData($node, 'extractPath'); // ✨ NEW: Support for nested path extraction

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

                // ✨ NEW: Apply path extraction if specified
                if (!empty($extractPath)) {
                    $extractedValue = $this->extractValueByPath($rawValue, $extractPath);

                    if ($extractedValue === null) {
                        return $this->error("Path '{$extractPath}' not found in source variable '{$sourceVariable}'");
                    }

                    $rawValue = $extractedValue;
                    $message = "Variable '{$variableName}' set from source variable '{$sourceVariable}.{$extractPath}'";
                } else {
                    $message = "Variable '{$variableName}' set from source variable '{$sourceVariable}'";
                }
            } else {
                // Use manual input value
                $variableValue = $this->getData($node, 'variableValue');
                if ($variableValue === null || $variableValue === '') {
                    return $this->error('Variable value is required when not using extracted value');
                }

                // Replace variables in the value (only if it's a string)
                if (is_string($variableValue)) {
                    $rawValue = $this->replaceVariables($variableValue, $variables);
                } else {
                    $rawValue = $variableValue;
                }
                $message = "Variable '{$variableName}' set from manual input";
            }

            // Convert value based on type
            $finalValue = $this->convertValueType($rawValue, $valueType);

            // Prepare value for storage (convert arrays/objects to JSON for cache)
            $storageValue = $this->prepareForStorage($finalValue);

            $output = [$variableName => $finalValue];

            // Handle persistent storage
            if ($persistent) {
                $cacheKey = "witchcraft_var_{$variableName}";
                if (!empty($cacheExpiry) && is_numeric($cacheExpiry)) {
                    Cache::put($cacheKey, $storageValue, now()->addMinutes((int)$cacheExpiry));
                    $message .= " in cache with {$cacheExpiry} minute expiry";
                } else {
                    Cache::forever($cacheKey, $storageValue);
                    $message .= " in persistent cache";
                }
            } else {
                $message .= " in workflow memory";
            }

            // Always append the 'extractedValue' to the output for edge routing
            $output['extractedValue'] = $finalValue;
            $message .= " with extracted value";

            return $this->success($output, $message);
        } catch (\Exception $e) {
            return $this->error("Failed to set variable: " . $e->getMessage());
        }
    }

    /**
     * ✨ NEW: Extract value from object/array using dot notation path
     * Same implementation as FlowVariableHandler for consistency
     *
     * @param mixed $data The data to extract from
     * @param string $path The dot notation path (e.g., 'user.email' or 'data.0.name')
     * @return mixed The extracted value or null if not found
     */
    private function extractValueByPath($data, string $path)
    {
        if (empty($path)) {
            return $data;
        }

        $keys = explode('.', $path);
        $current = $data;

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
            // Handle object properties
            elseif (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            }
            // Handle object properties (if it's a stdClass or similar)
            elseif (is_object($current) && property_exists($current, $key)) {
                $current = $current->$key;
            }
            // Handle object properties (using array access for objects that support it)
            elseif (is_object($current) && method_exists($current, 'offsetExists') && $current->offsetExists($key)) {
                $current = $current[$key];
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Convert value based on specified type
     */
    private function convertValueType($value, $type)
    {
        return match ($type) {
            'number' => $this->convertToNumber($value),
            'boolean' => $this->convertToBoolean($value),
            'json' => $this->convertToJson($value),
            'array' => $this->convertToArray($value),
            'object' => $this->convertToObject($value),
            default => $this->convertToString($value)
        };
    }

    /**
     * Convert value to number
     */
    private function convertToNumber($value)
    {
        if (is_array($value) || is_object($value)) {
            return 0; // Default for complex types
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
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Convert value to JSON string
     */
    private function convertToJson($value)
    {
        if (is_string($value)) {
            // Check if it's already valid JSON
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $value; // Already valid JSON
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

        return [$value]; // Wrap single values in array
    }

    /**
     * Convert value to object
     */
    private function convertToObject($value)
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
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return (object)['value' => $value];
    }

    /**
     * Convert value to string safely
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
     * Prepare value for storage (cache requires serializable data)
     */
    private function prepareForStorage($value)
    {
        // For cache storage, we need to ensure the data is serializable
        if (is_object($value)) {
            // Convert objects to arrays for storage
            return json_decode(json_encode($value), true);
        }

        return $value;
    }

    /**
     * ✨ NEW: Debug helper method to validate paths during development
     * This can be used for testing path extraction
     */
    public function validatePath($data, string $path): array
    {
        if (empty($path)) {
            return [
                'valid' => true,
                'message' => 'No path specified, will return original value',
                'result_type' => gettype($data)
            ];
        }

        try {
            $result = $this->extractValueByPath($data, $path);

            if ($result === null) {
                return [
                    'valid' => false,
                    'message' => "Path '{$path}' does not exist in the provided data",
                    'available_paths' => $this->getAvailablePaths($data)
                ];
            }

            return [
                'valid' => true,
                'message' => "Path '{$path}' is valid",
                'result_type' => gettype($result),
                'result_preview' => is_string($result) && strlen($result) > 50
                    ? substr($result, 0, 50) . '...'
                    : $result
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => "Error validating path: " . $e->getMessage()
            ];
        }
    }

    /**
     * ✨ NEW: Get all available paths for a given data structure
     * Useful for debugging and path discovery
     */
    private function getAvailablePaths($data, string $prefix = '', int $maxDepth = 3, int $currentDepth = 0): array
    {
        $paths = [];

        if ($currentDepth >= $maxDepth) {
            return $paths;
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $newPath = $prefix ? $prefix . '.' . $key : $key;
                $paths[] = $newPath;

                if (is_array($value) || is_object($value)) {
                    $subPaths = $this->getAvailablePaths($value, $newPath, $maxDepth, $currentDepth + 1);
                    $paths = array_merge($paths, $subPaths);
                }
            }
        } elseif (is_object($data)) {
            $properties = get_object_vars($data);
            foreach ($properties as $key => $value) {
                $newPath = $prefix ? $prefix . '.' . $key : $key;
                $paths[] = $newPath;

                if (is_array($value) || is_object($value)) {
                    $subPaths = $this->getAvailablePaths($value, $newPath, $maxDepth, $currentDepth + 1);
                    $paths = array_merge($paths, $subPaths);
                }
            }
        }

        return $paths;
    }
}
