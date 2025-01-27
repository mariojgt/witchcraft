<?php

namespace Mariojgt\Witchcraft\Controllers\Node;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class ModelSelectController extends Controller
{
    public function getModels()
    {
        $tables = DB::select('SHOW TABLES');
        $tableNames = array_map(function ($table) {
            return $table->{key($table)};
        }, $tables);

        return response()->json($tableNames);
    }

    public function getFields($modelName)
    {
        $fields = Schema::getColumnListing($modelName);

        return response()->json($fields);
    }
}
