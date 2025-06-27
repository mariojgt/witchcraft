<?php

namespace Mariojgt\Witchcraft\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Mariojgt\Witchcraft\Models\FlowDiagram;
use Mariojgt\Witchcraft\Models\FlowSimulationRun;
use Mariojgt\Witchcraft\Services\FlowExecutor;

class FlowDiagramController extends Controller
{
    /**
     * Get all flow diagrams (latest versions only by default)
     */
    public function index(Request $request)
    {
        $query = FlowDiagram::query();

        // By default, show only latest versions
        if (!$request->has('include_all_versions')) {
            $query->latestVersions();
        }

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

        $allowedOrderFields = ['created_at', 'updated_at', 'name', 'category', 'version'];
        if (in_array($orderBy, $allowedOrderFields)) {
            $query->orderBy($orderBy, $orderDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Handle pagination vs all results
        if ($request->has('per_page')) {
            $perPage = min((int)$request->per_page, 100);
            return response()->json($query->paginate($perPage));
        }

        $results = $query->get();
        return response()->json($results);
    }

    /**
     * Store a new flow diagram with enhanced fields and change detection
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:100',
            'trigger_code' => 'nullable|string|max:100|unique:flow_diagrams,trigger_code',
            'nodes' => 'required|string',
            'edges' => 'required|string',
            'is_deletable' => 'boolean',
            'version_notes' => 'nullable|string|max:500'
        ]);

        // Validate JSON
        $nodesArray = json_decode($validated['nodes'], true);
        $edgesArray = json_decode($validated['edges'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON format in nodes or edges'
            ], 422);
        }

        // Validate icon is in allowed list
        if (isset($validated['icon']) && !array_key_exists($validated['icon'], FlowDiagram::$availableIcons)) {
            $validated['icon'] = 'WorkflowIcon';
        }

        $diagram = FlowDiagram::create($validated);

        return response()->json($diagram, 201);
    }

    /**
     * Update a flow diagram with versioning support
     */
    public function update(Request $request, int $flow)
    {
        $flowDiagram = FlowDiagram::findOrFail($flow);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'category' => 'nullable|string|max:100',
            'icon' => 'nullable|string|max:100',
            'trigger_code' => 'nullable|string|max:100',
            'nodes' => 'required|string',
            'edges' => 'required|string',
            'is_deletable' => 'boolean',
            'version_notes' => 'nullable|string|max:500'
        ]);

        // Validate JSON
        $nodesArray = json_decode($validated['nodes'], true);
        $edgesArray = json_decode($validated['edges'], true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid JSON format in nodes or edges'
            ], 422);
        }

        // Validate icon
        if (isset($validated['icon']) && !array_key_exists($validated['icon'], FlowDiagram::$availableIcons)) {
            $validated['icon'] = 'WorkflowIcon';
        }

        // Check if nodes or edges changed and create new version if needed
        $needsNewVersion = $flowDiagram->is_latest_version &&
                          ($flowDiagram->nodes !== $validated['nodes'] ||
                           $flowDiagram->edges !== $validated['edges']);

        if ($needsNewVersion) {
            // Step 1: Store the original trigger code
            $originalTriggerCode = $flowDiagram->trigger_code;

            // Step 2: Update current version to be old version
            $oldTriggerCode = $originalTriggerCode . '_OLD_V' . $flowDiagram->version;

            // Make sure old trigger code is unique
            $counter = 1;
            $testCode = $oldTriggerCode;
            while (FlowDiagram::where('trigger_code', $testCode)->where('id', '!=', $flowDiagram->id)->exists()) {
                $testCode = $oldTriggerCode . '_' . $counter;
                $counter++;
                if ($counter > 99) {
                    $testCode = $oldTriggerCode . '_' . time();
                    break;
                }
            }

            // Update current diagram to be old version
            $flowDiagram->update([
                'is_latest_version' => false,
                'trigger_code' => $testCode
            ]);

            // Step 3: Create new version with original trigger code
            $newVersion = new FlowDiagram();
            $newVersion->fill($validated);
            $newVersion->trigger_code = $originalTriggerCode; // Keep original trigger code
            $newVersion->version = $flowDiagram->version + 1;
            $newVersion->parent_diagram_id = $flowDiagram->parent_diagram_id ?: $flowDiagram->id;
            $newVersion->is_latest_version = true;
            $newVersion->save();

            return response()->json($newVersion);
        } else {
            // Just update existing (no structural changes)
            $flowDiagram->update($validated);
            return response()->json($flowDiagram);
        }
    }

    /**
     * Delete a flow diagram (only if deletable)
     */
    public function destroy(int $flow)
    {
        $flowDiagram = FlowDiagram::findOrFail($flow);

        if (!$flowDiagram->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'This workflow is protected and cannot be deleted'
            ], 403);
        }

        $flowDiagram->delete();
        return response()->json(null, 204);
    }

    /**
     * Get all versions of a specific diagram
     */
    public function versions(int $flow)
    {
        $flowDiagram = FlowDiagram::findOrFail($flow);
        $versions = $flowDiagram->getAllVersions();

        return response()->json($versions);
    }

    /**
     * Load a specific version data (for editing, not saving)
     */
    public function restore(int $flow)
    {
        $versionToLoad = FlowDiagram::findOrFail($flow);

        // Return the version data for loading into the editor
        // The frontend will load this data and let user modify/save
        return response()->json([
            'success' => true,
            'message' => "Version {$versionToLoad->version} loaded for editing",
            'version_data' => [
                'name' => $versionToLoad->name,
                'description' => $versionToLoad->description,
                'category' => $versionToLoad->category,
                'icon' => $versionToLoad->icon,
                'nodes' => json_decode($versionToLoad->nodes, true),
                'edges' => json_decode($versionToLoad->edges, true),
                'is_deletable' => $versionToLoad->is_deletable,
                'version_info' => [
                    'restored_from_version' => $versionToLoad->version,
                    'original_version_notes' => $versionToLoad->version_notes,
                    'restored_at' => now()->toISOString()
                ]
            ]
        ]);
    }

    /**
     * Get simulation runs for a specific diagram
     */
    public function simulationRuns(int $flow)
    {
        $flowDiagram = FlowDiagram::findOrFail($flow);
        $runs = FlowSimulationRun::getLatestRuns($flow, 20);

        return response()->json($runs);
    }

    /**
     * Store simulation run result
     */
    public function storeSimulationRun(Request $request, int $flow)
    {
        $validated = $request->validate([
            'execution_log' => 'required|array',
            'final_variables' => 'nullable|array',
            'status' => 'required|in:success,error,partial',
            'error_message' => 'nullable|string',
            'total_nodes' => 'required|integer|min:0',
            'completed_nodes' => 'required|integer|min:0',
            'started_at' => 'required|date',
            'completed_at' => 'nullable|date',
            'duration_ms' => 'nullable|integer|min:0'
        ]);

        $validated['flow_diagram_id'] = $flow;

        $simulationRun = FlowSimulationRun::create($validated);

        return response()->json($simulationRun, 201);
    }

    /**
     * Get available icons
     */
    public function availableIcons()
    {
        return response()->json(FlowDiagram::getAvailableIcons());
    }

    /**
     * Get available categories
     */
    public function categories()
    {
        return response()->json(FlowDiagram::getCategories());
    }

    /**
     * Get flow statistics with enhanced metrics
     */
    public function statistics()
    {
        $stats = [
            'total_flows' => FlowDiagram::latestVersions()->count(),
            'created_today' => FlowDiagram::latestVersions()->whereDate('created_at', today())->count(),
            'created_this_week' => FlowDiagram::latestVersions()->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'created_this_month' => FlowDiagram::latestVersions()->whereMonth('created_at', now()->month)->count(),
            'categories' => FlowDiagram::getCategories(),
            'by_category' => FlowDiagram::latestVersions()
                                      ->selectRaw('category, COUNT(*) as count')
                                      ->whereNotNull('category')
                                      ->groupBy('category')
                                      ->orderBy('count', 'desc')
                                      ->pluck('count', 'category')
                                      ->toArray(),
            'protected_workflows' => FlowDiagram::latestVersions()->where('is_deletable', false)->count(),
            'total_versions' => FlowDiagram::count(),
        ];

        return response()->json($stats);
    }

    /**
     * Execute a flow diagram by trigger code (latest version only)
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
                    'version' => $flowDiagram->version,
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

    // ... (keep existing methods: show, execute, executeById, simulateNode, forSelection)

    public function show(int $flow)
    {
        $flowDiagram = FlowDiagram::findOrFail($flow);
        return response()->json($flowDiagram);
    }

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

    public function forSelection(Request $request)
    {
        $query = FlowDiagram::select('id', 'name', 'description', 'category', 'icon', 'trigger_code', 'created_at')
                           ->latestVersions();

        if ($request->has('q') && !empty($request->q)) {
            $query->search($request->q);
        }

        if ($request->has('category') && !empty($request->category)) {
            $query->byCategory($request->category);
        }

        $limit = min((int)$request->get('limit', 20), 50);
        $results = $query->orderBy('name')
                        ->limit($limit)
                        ->get();

        return response()->json($results);
    }
}
