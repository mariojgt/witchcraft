<?php

namespace Mariojgt\Witchcraft\Controllers\Node;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class ModelUtilityController extends Controller
{
    /**
     * Get available database tables
     */
    public function getTables()
    {
        try {
            $tables = DB::select('SHOW TABLES');
            $tableNames = array_map(function ($table) {
                // Get the table name, which is usually the first value in the associative array
                return reset($table);
            }, $tables);

            return response()->json($tableNames);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error("Failed to fetch tables: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch tables.'], 500);
        }
    }

    /**
     * Get columns for a specific table
     */
    public function getTableColumns($tableName)
    {
        try {
            if (!Schema::hasTable($tableName)) {
                return response()->json(['error' => 'Table not found'], 404);
            }

            $columns = Schema::getColumnListing($tableName);
            return response()->json($columns);
        } catch (\Exception $e) {
            \Log::error("Failed to fetch columns for table {$tableName}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch table columns.'], 500);
        }
    }

    /**
     * Get a specific record by ID from a table.
     * This is the new method for the Model Lookup node.
     *
     * @param string $tableName The name of the database table.
     * @param mixed $id The ID of the record to fetch.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecord($tableName, $id)
    {
        try {
            if (!Schema::hasTable($tableName)) {
                return response()->json(['error' => 'Table not found'], 404);
            }

            // Fetch the record using DB::table and find()
            // find() assumes the primary key is 'id'. If it's different,
            // you'll need to use ->where('your_custom_id_column', $id)->first().
            $record = DB::table($tableName)->find($id);

            if (!$record) {
                return response()->json(['error' => 'Record not found'], 404);
            }

            // Return the record as a JSON response
            return response()->json($record);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error("Failed to fetch record ID {$id} from table {$tableName}: " . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch record.'], 500);
        }
    }
}
