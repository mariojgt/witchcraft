<?php

namespace Mariojgt\Witchcraft\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use App\Http\Controllers\Controller;

class NodeController extends Controller
{
    public function getAvailableNodes()
    {
        $nodes = [];

        // Get custom nodes from app/Witchcraft/Handlers
        $handlersPath = app_path('Witchcraft/Handlers');
        if (File::exists($handlersPath)) {
            $files = File::files($handlersPath);

            foreach ($files as $file) {
                $className = 'App\\Witchcraft\\Handlers\\' . $file->getBasename('.php');
                if (class_exists($className)) {
                    $handler = new $className();
                    if (method_exists($handler, 'getMetadata')) {
                        $nodes[] = $handler->getMetadata();
                    }
                }
            }
        }

        return response()->json($nodes);
    }
}
