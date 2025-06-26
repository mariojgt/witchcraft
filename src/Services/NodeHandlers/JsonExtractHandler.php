<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class JsonExtractHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        $sourceVariable = $this->getData($node, 'sourceVariable');
        $jsonPath = $this->getData($node, 'jsonPath');
        $outputKey = $this->getData($node, 'outputKey', 'extractedValue');

        if (!$sourceVariable || !$jsonPath) {
            return $this->error('Source variable and JSON path are required');
        }

        // Get the source JSON from variables
        $sourceJson = $variables[$sourceVariable] ?? null;

        if (!$sourceJson) {
            return $this->error("Source variable {$sourceVariable} not found");
        }

        // Trasnform the $sourceJson to an array if it's a JSON string
        if (is_string($sourceJson)) {
            $sourceJson = json_decode($sourceJson, true);
        } else {
            // If is a object, convert it to an array
            if (is_object($sourceJson)) {
                $sourceJson = json_decode(json_encode($sourceJson), true);
            }
        }
        // Use Laravel's data_get helper to extract nested values
        $extractedValue = data_get($sourceJson, $jsonPath);

        return $this->success([
            $outputKey => $extractedValue,
            'extractedValue' => $extractedValue
        ], "Extracted {$jsonPath} from {$sourceVariable}");
    }
}
