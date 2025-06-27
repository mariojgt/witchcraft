<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class FlowVariableHandler extends BaseNodeHandler
{
    /**
     * Handle the Flow Variable node execution
     *
     * @param array $node The node configuration
     * @param array $variables The current workflow variables
     * @return array The execution result
     */
    public function handle(array $node, array $variables): array
    {
        try {
            // Get node configuration
            $variableName = $this->getData($node, 'variableName');
            $extractPath = $this->getData($node, 'extractPath');
            $defaultValue = $this->getData($node, 'defaultValue');
            $failIfNotFound = $this->getData($node, 'failIfNotFound', false);

            // Validate required fields
            if (empty($variableName)) {
                return $this->error('Variable name is required');
            }

            // Check if variable exists in workflow variables
            if (!isset($variables[$variableName])) {
                if ($failIfNotFound) {
                    return $this->error("Variable '{$variableName}' not found in workflow variables");
                }

                // Use default value if variable not found
                $extractedValue = $defaultValue;
                $message = "Variable '{$variableName}' not found, using default value";

                return $this->success([
                    'extractedValue' => $extractedValue,
                    'variableSource' => 'default',
                    'variableName' => $variableName,
                    'originalValue' => null
                ], $message);
            }

            // Get the variable value
            $originalValue = $variables[$variableName];
            $extractedValue = $originalValue;

            // Apply path extraction if specified
            if (!empty($extractPath)) {
                $extractedValue = $this->extractValueByPath($originalValue, $extractPath);

                // If extraction failed and we have a default value
                if ($extractedValue === null && $defaultValue !== null) {
                    $extractedValue = $defaultValue;
                    $message = "Extracted value from '{$variableName}.{$extractPath}' was null, using default value";
                } else {
                    $message = "Successfully extracted value from '{$variableName}.{$extractPath}'";
                }
            } else {
                $message = "Successfully retrieved variable '{$variableName}'";
            }

            // Prepare output
            $output = [
                'extractedValue' => $extractedValue,
                'variableSource' => 'workflow',
                'variableName' => $variableName,
                'originalValue' => $originalValue
            ];

            // Add path info if extraction was used
            if (!empty($extractPath)) {
                $output['extractPath'] = $extractPath;
                $output['pathExtracted'] = true;
            }

            return $this->success($output, $message);
        } catch (\Exception $e) {
            return $this->error("Failed to process flow variable: " . $e->getMessage());
        }
    }

    /**
     * Extract value from object/array using dot notation path
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
     * Get debug information about available variables
     * This method can be called during development to see what variables are available
     *
     * @param array $variables The current workflow variables
     * @return array Debug information
     */
    public function debugVariables(array $variables): array
    {
        $debug = [
            'total_variables' => count($variables),
            'variable_names' => array_keys($variables),
            'variable_types' => [],
            'variable_samples' => []
        ];

        foreach ($variables as $name => $value) {
            $debug['variable_types'][$name] = gettype($value);

            // Create a sample/preview of the value
            if (is_string($value)) {
                $debug['variable_samples'][$name] = strlen($value) > 50
                    ? substr($value, 0, 50) . '...'
                    : $value;
            } elseif (is_array($value)) {
                $debug['variable_samples'][$name] = count($value) . ' items: [' .
                    implode(', ', array_slice(array_keys($value), 0, 3)) .
                    (count($value) > 3 ? ', ...' : '') . ']';
            } elseif (is_object($value)) {
                $debug['variable_samples'][$name] = get_class($value) . ' object';
            } else {
                $debug['variable_samples'][$name] = $value;
            }
        }

        return $debug;
    }

    /**
     * Validate if a path is valid for a given value
     * Useful for testing paths before execution
     *
     * @param mixed $data The data to test against
     * @param string $path The path to validate
     * @return array Validation result with success status and details
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
     * Get all available paths for a given data structure
     * Useful for debugging and path discovery
     *
     * @param mixed $data The data to analyze
     * @param string $prefix Current path prefix
     * @param int $maxDepth Maximum depth to traverse
     * @param int $currentDepth Current traversal depth
     * @return array Available paths
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
