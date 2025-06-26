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
                return reset($table);
            }, $tables);

            return response()->json($tableNames);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
