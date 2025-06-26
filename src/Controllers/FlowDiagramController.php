<?php

namespace Mariojgt\Witchcraft\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mariojgt\Witchcraft\Models\FlowDiagram;
use Mariojgt\Witchcraft\Services\FlowExecutor;

class FlowDiagramController extends Controller
{
    /**
     * Get all flow diagrams with search and pagination
     */
    public function index(Request $request)
    {
        $query = FlowDiagram::query();

        // Add search functionality
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('id', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Add filtering by creation date
        if ($request->has('created_after')) {
            $query->where('created_at', '>=', $request->created_after);
        }

        // Add ordering
        $orderBy = $request->get('order_by', 'created_at');
        $orderDirection = $request->get('order_direction', 'desc');
        $query->orderBy($orderBy, $orderDirection);

        // Handle pagination vs all results
        if ($request->has('per_page')) {
            $perPage = min((int)$request->per_page, 100); // Max 100 per page
            return response()->json($query->paginate($perPage));
        }

        // Return all results (for dropdown usage)
        $results = $query->get();

        return response()->json($results);
    }

    /**
     * Get flow diagrams for selection dropdown (optimized)
     */
    public function forSelection(Request $request)
    {
        $query = FlowDiagram::select('id', 'name', 'description', 'created_at');

        // Search functionality
        if ($request->has('q') && !empty($request->q)) {
            $searchTerm = $request->q;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('description', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('id', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Limit results for dropdown
        $limit = min((int)$request->get('limit', 20), 50);
        $results = $query->orderBy('name')
                        ->limit($limit)
                        ->get();

        return response()->json($results);
    }

    /**
     * Store a new flow diagram
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'nodes' => 'required|string',  // Expecting JSON string from frontend
            'edges' => 'required|string',  // Expecting JSON string from frontend
        ]);

        // Validate that the JSON strings are valid JSON
        $nodesArray = json_decode($validated['nodes'], true);
        $edgesArray = json_decode($validated['edges'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON format in nodes or edges');
        }

        // Store the JSON strings directly (don't convert to arrays)
        $diagram = FlowDiagram::create($validated);

        return response()->json($diagram, 201);
    }

    /**
     * Show a specific flow diagram
     */
    public function show(int $flow)
    {
        $flowDiagram = FlowDiagram::findOrFail($flow);
        return response()->json($flowDiagram);
    }

    /**
     * Update a flow diagram
     */
    public function update(Request $request, int $flow)
    {
        $flowDiagram = FlowDiagram::findOrFail($flow);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'nodes' => 'required|string',  // Expecting JSON string from frontend
            'edges' => 'required|string',  // Expecting JSON string from frontend
        ]);

        // Validate that the JSON strings are valid JSON
        $nodesArray = json_decode($validated['nodes'], true);
        $edgesArray = json_decode($validated['edges'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Invalid JSON format in nodes or edges');
        }

        // Store the JSON strings directly (don't convert to arrays)
        $flowDiagram->update($validated);

        return response()->json($flowDiagram);
    }

    /**
     * Delete a flow diagram
     */
    public function destroy(int $flow)
    {
        $flowDiagram = FlowDiagram::findOrFail($flow);
        $flowDiagram->delete();
        return response()->json(null, 204);
    }

    /**
     * Execute a flow diagram
     */
    public function execute(Request $request, FlowDiagram $flowDiagram)
    {
        try {
            $initialData = $request->input('data', []);

            $executor = new FlowExecutor($flowDiagram);
            $result = $executor->run($initialData);

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

    /**
     * Execute a flow by ID with initial data (for TriggerFlow node)
     */
    public function executeById(Request $request, int $flowId)
    {
        try {
            $flowDiagram = FlowDiagram::findOrFail($flowId);
            $initialData = $request->input('data', []);

            $executor = new FlowExecutor($flowDiagram);
            $result = $executor->run($initialData);

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

    /**
     * Simulate a single node (for testing)
     */
    public function simulateNode(Request $request)
    {
        try {
            $validated = $request->validate([
                'node' => 'required|array',
                'variables' => 'array'
            ]);

            $executor = new FlowExecutor();
            $result = $executor->processNode($validated['node'], $validated['variables'] ?? []);

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

    /**
     * Get flow statistics
     */
    public function statistics()
    {
        $stats = [
            'total_flows' => FlowDiagram::count(),
            'created_today' => FlowDiagram::whereDate('created_at', today())->count(),
            'created_this_week' => FlowDiagram::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'created_this_month' => FlowDiagram::whereMonth('created_at', now()->month)->count(),
        ];

        return response()->json($stats);
    }
}
