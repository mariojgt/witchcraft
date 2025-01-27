<?php

namespace Mariojgt\Witchcraft\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mariojgt\Witchcraft\Models\FlowDiagram;
use Mariojgt\Witchcraft\Services\NodeProcessor;
use Mariojgt\Witchcraft\Services\FlowDiagramService;

class FlowDiagramController extends Controller
{
    public function index()
    {
        return response()->json(FlowDiagram::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'nodes' => 'required|string', // We'll get this as a JSON string
            'edges' => 'required|string', // We'll get this as a JSON string
        ]);

        // Decode JSON strings to ensure they're valid before saving
        $validated['nodes'] = json_decode($validated['nodes'], true);
        $validated['edges'] = json_decode($validated['edges'], true);

        $diagram = FlowDiagram::create($validated);

        return response()->json($diagram, 201);
    }

    public function show(int $flowDiagram)
    {
        $flowDiagram = FlowDiagram::findOrFail($flowDiagram);
        return response()->json($flowDiagram);
    }

    public function update(Request $request, int $flowDiagram)
    {
        $flowDiagram = FlowDiagram::findOrFail($flowDiagram);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'nodes' => 'required|string', // We'll get this as a JSON string
            'edges' => 'required|string', // We'll get this as a JSON string
        ]);

        // Decode JSON strings to ensure they're valid before saving
        $validated['nodes'] = json_decode($validated['nodes'], true);
        $validated['edges'] = json_decode($validated['edges'], true);

        $flowDiagram->update($validated);

        return response()->json($flowDiagram);
    }

    public function destroy(int $flowDiagram)
    {
        $flowDiagram = FlowDiagram::findOrFail($flowDiagram);
        $flowDiagram->delete();
        return response()->json(null, 204);
    }

    public function execute(Request $request, FlowDiagram $flowDiagram)
    {
        $service = new FlowDiagramService($flowDiagram);
        $result = $service->execute($request->input('data', []));
        return response()->json($result);
    }

    public function processNode(Request $request)
    {
        try {
            $validated = $request->validate([
                'node' => 'required|array',
                'variables' => 'array'
            ]);

            $processor = new NodeProcessor();
            $result = $processor->processNode(
                $validated['node'],
                $validated['variables'] ?? []
            );

            return response()->json([
                'success' => true,
                'result' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
