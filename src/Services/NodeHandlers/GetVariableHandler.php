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

            if (empty($variableName)) {
                return $this->error('Variable name is required');
            }

            $value = null;
            $source = 'not found';

            // Try to get from cache first if specified
            if ($fromCache) {
                $cacheKey = "witchcraft_var_{$variableName}";
                $value = Cache::get($cacheKey);
                if ($value !== null) {
                    $source = 'cache';
                }
            }

            // If not found in cache (or cache not requested), try workflow variables
            if ($value === null && isset($variables[$variableName])) {
                $value = $variables[$variableName];
                $source = 'workflow';
            }

            // Use default value if still not found
            if ($value === null) {
                if ($failIfNotFound) {
                    return $this->error("Variable '{$variableName}' not found and failIfNotFound is enabled");
                }

                $value = $defaultValue;
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

            return $this->success($output, $message);
        } catch (\Exception $e) {
            return $this->error("Failed to get variable: " . $e->getMessage());
        }
    }
}
