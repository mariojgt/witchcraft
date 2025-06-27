<?php

namespace Mariojgt\Witchcraft\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mariojgt\Witchcraft\Models\FlowDiagram;
use Mariojgt\Witchcraft\Services\FlowExecutor;

class FlowDiagramController extends Controller
{
    /**
     * Get all flow diagrams with enhanced search and pagination
     */
    public function index(Request $request)
    {
        $query = FlowDiagram::query();

        // Enhanced search functionality
        if ($request->has('search') && !empty($request->search)) {
            $query->search($request->search);
        }

        // Category filtering
        if ($request->has('category') && !empty($request->category)) {
            $query->byCategory($request->category);
        }

        // Add filtering by creation date
        if ($request->has('created_after')) {
            $query->where('created_at', '>=', $request->created_after);
        }

        // Enhanced ordering with new fields
        $orderBy = $request->get('order_by', 'created_at');
        $orderDirection = $request->get('order_direction', 'desc');

        // Validate order_by field to prevent SQL injection
        $allowedOrderFields = ['created_at', 'updated_at', 'name', 'category'];
        if (in_array($orderBy, $allowedOrderFields)) {
            $query->orderBy($orderBy, $orderDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

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
        $query = FlowDiagram::select('id', 'name', 'description', 'category', 'icon', 'trigger_code', 'created_at');

        // Search functionality
        if ($request->has('q') && !empty($request->q)) {
            $query->search($request->q);
        }

        // Category filter
        if ($request->has('category') && !empty($request->category)) {
            $query->byCategory($request->category);
        }

        // Limit results for dropdown
        $limit = min((int)$request->get('limit', 20), 50);
        $results = $query->orderBy('name')
                        ->limit($limit)
                        ->get();

        return response()->json($results);
    }

    /**
     * Get available categories
     */
    public function categories()
    {
        $categories = FlowDiagram::getCategories();
        return response()->json($categories);
    }

    /**
     * Store a new flow diagram with enhanced fields
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:100',
            'trigger_code' => 'nullable|string|max:100|unique:flow_diagrams,trigger_code',
            'nodes' => 'required|string',  // Expecting JSON string from frontend
            'edges' => 'required|string',  // Expecting JSON string from frontend
        ]);

        // Validate that the JSON strings are valid JSON
        $nodesArray = json_decode($validated['nodes'], true);
        $edgesArray = json_decode($validated['edges'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON format in nodes or edges'
            ], 422);
        }

        // Create the diagram (trigger_code will be auto-generated if not provided)
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
     * Update a flow diagram with enhanced fields
     */
    public function update(Request $request, int $flow)
    {
        $flowDiagram = FlowDiagram::findOrFail($flow);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:100',
            'trigger_code' => 'nullable|string|max:100|unique:flow_diagrams,trigger_code,' . $flow,
            'nodes' => 'required|string',  // Expecting JSON string from frontend
            'edges' => 'required|string',  // Expecting JSON string from frontend
        ]);

        // Validate that the JSON strings are valid JSON
        $nodesArray = json_decode($validated['nodes'], true);
        $edgesArray = json_decode($validated['edges'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON format in nodes or edges'
            ], 422);
        }

        // Update the diagram
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
     * Execute a flow diagram by trigger code
     */
    public function executeByTriggerCode(Request $request, string $triggerCode)
    {
        try {
            $flowDiagram = FlowDiagram::findByTriggerCode($triggerCode);

            if (!$flowDiagram) {
                return response()->json([
                    'success' => false,
                    'error' => "Flow diagram with trigger code '{$triggerCode}' not found"
                ], 404);
            }

            $initialData = $request->input('data', []);

            $executor = new FlowExecutor($flowDiagram);
            $result = $executor->run($initialData);

            return response()->json([
                'success' => true,
                'result' => $result,
                'flow_info' => [
                    'id' => $flowDiagram->id,
                    'name' => $flowDiagram->name,
                    'trigger_code' => $flowDiagram->trigger_code
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 400);
        }
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
     * Get flow statistics with enhanced metrics
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
            'categories' => FlowDiagram::getCategories(),
            'by_category' => FlowDiagram::selectRaw('category, COUNT(*) as count')
                                      ->whereNotNull('category')
                                      ->groupBy('category')
                                      ->orderBy('count', 'desc')
                                      ->pluck('count', 'category')
                                      ->toArray(),
        ];

        return response()->json($stats);
    }
}
