<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class ModelSelectHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        $modelName = $this->getData($node, 'modelName');
        $eventType = $this->getData($node, 'eventType');
        $watchFields = $this->getData($node, 'watchFields', []);
        $outputKey = $this->getData($node, 'outputKey', 'model');
        $testMode = $this->getData($node, 'testMode', false);

        if (!$modelName) {
            return $this->error('Model name is required');
        }

        // If test mode is enabled, fetch the first random record
        if ($testMode) {
            try {
                $model = \DB::table($modelName)->first();

                if (!$model) {
                    return $this->error("No test data found for model: {$modelName}");
                }

                $model = (array)$model;
            } catch (\Exception $e) {
                return $this->error("Error fetching test data: " . $e->getMessage());
            }
        } else {
            // Normal production mode
            $model = $variables['model'] ?? null;
        }

        if (!$model) {
            return $this->error("No model found for event: {$modelName} - {$eventType}");
        }

        // Optional: Check watch fields
        if (!empty($watchFields)) {
            $changedFields = array_intersect($watchFields, array_keys($model));
            if (empty($changedFields)) {
                return $this->error("No watched fields found");
            }
        }

        return $this->success([
            $outputKey => $model
        ], "Model event registered: {$modelName} - {$eventType}");
    }
}
