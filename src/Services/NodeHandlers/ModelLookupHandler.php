<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
        $searchField = $this->getData($node, 'searchField', 'id');
        $sourceVariable = $this->getData($node, 'sourceVariable');
        $outputKey = $this->getData($node, 'outputKey', 'fetchedRecord');
        $fieldToExtract = $this->getData($node, 'fieldToExtract');
        $withRelations = $this->getData($node, 'withRelations', ''); // New: relationships to load
        $useEloquent = $this->getData($node, 'useEloquent', false); // New: force Eloquent usage

        // Determine the search value based on source variable or direct input
        $searchValue = null;
        if (!empty($sourceVariable)) {
            $searchValue = $this->getNestedVariableValue($variables, $sourceVariable);
            if ($searchValue === null) {
                return $this->success([
                    'conditionResult' => false
                ], "Source variable path '{$sourceVariable}' not found or is null in workflow variables.");
            }
        } elseif (isset($variables['extractedValue'])) {
            $searchValue = $variables['extractedValue'];
        } else {
            $searchValue = $this->getData($node, 'searchValue') ?: $this->getData($node, 'recordId');
        }

        // Validate required inputs
        if (empty($modelName)) {
            return $this->error('Model name is required for Model Lookup.');
        }

        if (empty($searchValue)) {
            return $this->error('Search value is required for Model Lookup (either direct or via source variable).');
        }

        // Default search field to 'id' if not specified
        if (empty($searchField)) {
            $searchField = 'id';
        }

        // Check if the table exists
        if (!Schema::hasTable($modelName)) {
            return $this->error("Table '{$modelName}' does not exist.");
        }

        // Check if the search field exists in the table
        if (!Schema::hasColumn($modelName, $searchField)) {
            return $this->error("Field '{$searchField}' does not exist in table '{$modelName}'.");
        }

        try {
            // Parse relationships
            $relationships = $this->parseRelationships($withRelations);

            // Decide whether to use Eloquent or Query Builder
            $shouldUseEloquent = $useEloquent || !empty($relationships);

            if ($shouldUseEloquent) {
                $record = $this->fetchWithEloquent($modelName, $searchField, $searchValue, $relationships);
            } else {
                $record = $this->fetchWithQueryBuilder($modelName, $searchField, $searchValue);
            }

            // Determine if a record was found
            $recordWasFound = (bool)$record;

            // Extract specific field if specified, otherwise return full record
            $extractedData = null;
            if ($record) {
                if ($shouldUseEloquent && $record instanceof Model) {
                    // Convert Eloquent model to array (includes relationships)
                    $recordArray = $record->toArray();
                } else {
                    $recordArray = (array)$record;
                }

                if (!empty($fieldToExtract)) {
                    $extractedData = $this->extractNestedField($recordArray, $fieldToExtract);
                    if ($extractedData === null && !$this->hasNestedField($recordArray, $fieldToExtract)) {
                        return $this->error("Field '{$fieldToExtract}' does not exist in the result.");
                    }
                } else {
                    $extractedData = $recordArray;
                }
            }

            $data = [
                'conditionResult' => $recordWasFound,
                $outputKey => $extractedData,
                'extractedValue' => $extractedData,
                'searchField' => $searchField,
                'searchValue' => $searchValue,
                'withRelations' => $relationships,
                'usedEloquent' => $shouldUseEloquent,
            ];

            $relationInfo = !empty($relationships) ? ' with relations: ' . implode(', ', $relationships) : '';
            $message = !empty($fieldToExtract)
                ? "Model Lookup completed for '{$searchField}' = '{$searchValue}' in '{$modelName}', field '{$fieldToExtract}' extracted{$relationInfo}."
                : "Model Lookup completed for '{$searchField}' = '{$searchValue}' in '{$modelName}'{$relationInfo}.";

            return $this->success($data, $message) + ['conditionResult' => $recordWasFound];
        } catch (\Exception $e) {
            return $this->error("Error fetching record: " . $e->getMessage());
        }
    }

    /**
     * Parse relationships from string input
     *
     * @param string $withRelations
     * @return array
     */
    private function parseRelationships(string $withRelations): array
    {
        if (empty($withRelations)) {
            return [];
        }

        // Split by comma and clean up
        return array_map('trim', explode(',', $withRelations));
    }

    /**
     * Fetch record using Eloquent (supports relationships)
     *
     * @param string $modelName
     * @param string $searchField
     * @param mixed $searchValue
     * @param array $relationships
     * @return Model|null
     */
    private function fetchWithEloquent(string $modelName, string $searchField, $searchValue, array $relationships = [])
    {
        // Try to find the Eloquent model class
        $modelClass = $this->getEloquentModelClass($modelName);

        if (!$modelClass || !class_exists($modelClass)) {
            throw new \Exception("Eloquent model class not found for table '{$modelName}'. Please ensure the model exists or disable relationship loading.");
        }

        $query = $modelClass::where($searchField, $searchValue);

        // Add relationships if specified
        if (!empty($relationships)) {
            $query->with($relationships);
        }

        return $query->first();
    }

    /**
     * Fetch record using Query Builder (no relationships)
     *
     * @param string $modelName
     * @param string $searchField
     * @param mixed $searchValue
     * @return object|null
     */
    private function fetchWithQueryBuilder(string $modelName, string $searchField, $searchValue)
    {
        return DB::table($modelName)
            ->where($searchField, $searchValue)
            ->first();
    }

    /**
     * Get Eloquent model class name from table name
     *
     * @param string $tableName
     * @return string|null
     */
    private function getEloquentModelClass(string $tableName): ?string
    {
        // Common naming conventions to try
        $attempts = [
            // Direct class name (if table name is already singular and properly cased)
            $tableName,

            // Singular form with proper case
            Str::studly(Str::singular($tableName)),

            // Plural form with proper case
            Str::studly($tableName),

            // With App\Models namespace (Laravel 8+)
            'App\\Models\\' . Str::studly(Str::singular($tableName)),
            'App\\Models\\' . Str::studly($tableName),

            // With App namespace (older Laravel)
            'App\\' . Str::studly(Str::singular($tableName)),
            'App\\' . Str::studly($tableName),
        ];

        foreach ($attempts as $className) {
            if (class_exists($className)) {
                // Verify it's actually an Eloquent model
                if (is_subclass_of($className, Model::class)) {
                    return $className;
                }
            }
        }

        return null;
    }

    /**
     * Extract nested field using dot notation
     *
     * @param array $data
     * @param string $fieldPath
     * @return mixed
     */
    private function extractNestedField(array $data, string $fieldPath)
    {
        $keys = explode('.', $fieldPath);
        $current = $data;

        foreach ($keys as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } else {
                return null;
            }
        }

        return $current;
    }

    /**
     * Check if nested field exists using dot notation
     *
     * @param array $data
     * @param string $fieldPath
     * @return bool
     */
    private function hasNestedField(array $data, string $fieldPath): bool
    {
        $keys = explode('.', $fieldPath);
        $current = $data;

        foreach ($keys as $key) {
            if (is_array($current) && array_key_exists($key, $current)) {
                $current = $current[$key];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Get nested variable value using dot notation
     *
     * @param array $variables
     * @param string $path
     * @return mixed
     */
    private function getNestedVariableValue(array $variables, string $path)
    {
        // Handle dot notation (e.g., user.email, report_data.product.id)
        if (strpos($path, '.') !== false) {
            $parts = explode('.', $path);
            $current = $variables;

            foreach ($parts as $part) {
                if (is_array($current) && array_key_exists($part, $current)) {
                    $current = $current[$part];
                } elseif (is_object($current) && property_exists($current, $part)) {
                    $current = $current->$part;
                } elseif (is_object($current) && method_exists($current, '__get')) {
                    $current = $current->__get($part);
                } else {
                    return null; // Path doesn't exist
                }
            }

            return $current;
        }

        // Simple variable access (no dot notation)
        return $variables[$path] ?? null;
    }

    /**
     * Get available relationships for a model
     * This can be used for UI suggestions
     *
     * @param string $tableName
     * @return array
     */
    public function getModelRelationships(string $tableName): array
    {
        try {
            $modelClass = $this->getEloquentModelClass($tableName);

            if (!$modelClass || !class_exists($modelClass)) {
                return [];
            }

            $model = new $modelClass;
            $relationships = [];

            // Get all methods that might be relationships
            $reflection = new \ReflectionClass($model);
            $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

            foreach ($methods as $method) {
                // Skip magic methods, getters, and common model methods
                if ($method->getDeclaringClass()->getName() === Model::class ||
                    $method->getDeclaringClass()->getName() === 'Illuminate\Database\Eloquent\Model' ||
                    Str::startsWith($method->getName(), ['get', 'set', 'is', 'has', 'scope']) ||
                    in_array($method->getName(), ['save', 'delete', 'update', 'create', 'find', 'where'])
                ) {
                    continue;
                }

                // Try to detect if it's a relationship method
                try {
                    $return = $method->invoke($model);
                    if ($return instanceof \Illuminate\Database\Eloquent\Relations\Relation) {
                        $relationships[] = $method->getName();
                    }
                } catch (\Exception $e) {
                    // Ignore methods that throw exceptions
                    continue;
                }
            }

            return $relationships;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get table columns (existing method)
     */
    public function getTableColumns(string $tableName): array
    {
        try {
            if (!Schema::hasTable($tableName)) {
                return [];
            }

            return Schema::getColumnListing($tableName);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Validate if a field exists in a table (existing method)
     */
    public function validateField(string $tableName, string $fieldName): bool
    {
        try {
            return Schema::hasTable($tableName) && Schema::hasColumn($tableName, $fieldName);
        } catch (\Exception $e) {
            return false;
        }
    }
}
