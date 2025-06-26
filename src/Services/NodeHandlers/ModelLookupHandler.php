<?php
namespace Mariojgt\Witchcraft\Services\NodeHandlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModelLookupHandler extends BaseNodeHandler
{
    /**
     * Handle the Model Lookup node execution.
     *
     * @param array $node The node configuration.
     * @param array $variables Current workflow variables.
     * @return array
     */
    public function handle(array $node, array $variables): array
    {
        $modelName = $this->getData($node, 'modelName');
        $sourceVariable = $this->getData($node, 'sourceVariable');
        $outputKey = $this->getData($node, 'outputKey', 'fetchedRecord');
        $fieldToExtract = $this->getData($node, 'fieldToExtract'); // New field

        // Determine the record ID based on source variable or direct input
        $recordId = null;
        if (!empty($sourceVariable) && isset($variables[$sourceVariable])) {
            $recordId = $variables[$sourceVariable];
        } elseif (isset($variables['extractedValue'])) {
            // Fallback to 'extractedValue' if no sourceVariable is specified or found
            $recordId = $variables['extractedValue'];
        } else {
            $recordId = $this->getData($node, 'recordId');
        }

        // Validate required inputs
        if (empty($modelName)) {
            return $this->error('Model name is required for Model Lookup.');
        }

        if (empty($recordId)) {
            return $this->error('Record ID is required for Model Lookup (either direct or via source variable).');
        }

        // Check if the table exists
        if (!Schema::hasTable($modelName)) {
            return $this->error("Table '{$modelName}' does not exist.");
        }

        try {
            $record = DB::table($modelName)->find($recordId);

            // Determine if a record was found
            $recordWasFound = (bool)$record;

            // Extract specific field if specified, otherwise return full record
            $extractedData = null;
            if ($record) {
                $recordArray = (array)$record;

                if (!empty($fieldToExtract)) {
                    // Check if the specified field exists
                    if (array_key_exists($fieldToExtract, $recordArray)) {
                        $extractedData = $recordArray[$fieldToExtract];
                    } else {
                        return $this->error("Field '{$fieldToExtract}' does not exist in table '{$modelName}'.");
                    }
                } else {
                    // Return full record if no specific field is requested
                    $extractedData = $recordArray;
                }
            }

            $data = [
                'modelFound' => $recordWasFound,
                $outputKey => $extractedData,
                'extractedValue' => $extractedData,
            ];

            $message = !empty($fieldToExtract)
                ? "Model Lookup completed for ID '{$recordId}' in '{$modelName}', field '{$fieldToExtract}' extracted."
                : "Model Lookup completed for ID '{$recordId}' in '{$modelName}'.";

            return $this->success($data, $message) + ['conditionResult' => $recordWasFound];
        } catch (\Exception $e) {
            return $this->error("Error fetching record: " . $e->getMessage());
        }
    }
}
