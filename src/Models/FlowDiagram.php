<?php

namespace Mariojgt\Witchcraft\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class FlowDiagram extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category',
        'icon',
        'trigger_code',
        'nodes',
        'edges',
        'is_active',
        'is_deletable',
        'version',
        'parent_diagram_id',
        'is_latest_version',
        'version_notes'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deletable' => 'boolean',
        'is_latest_version' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_deletable' => true,
        'version' => 1,
        'is_latest_version' => true
    ];

    // Predefined icon list for consistency
    public static $availableIcons = [
        'WorkflowIcon' => 'Workflow',
        'DatabaseIcon' => 'Database',
        'ApiIcon' => 'API',
        'CpuIcon' => 'Process',
        'BellIcon' => 'Notification',
        'ClockIcon' => 'Schedule',
        'GlobeIcon' => 'Web',
        'FileIcon' => 'File',
        'MailIcon' => 'Email',
        'SettingsIcon' => 'Settings',
        'ZapIcon' => 'Action',
        'FilterIcon' => 'Filter',
        'RepeatIcon' => 'Loop',
        'BranchesIcon' => 'Branch',
        'CheckCircleIcon' => 'Validation',
        'AlertTriangleIcon' => 'Warning',
        'InfoIcon' => 'Information',
        'LockIcon' => 'Security',
        'KeyIcon' => 'Authentication',
        'CloudIcon' => 'Cloud'
    ];

    /**
     * Boot the model and set up event listeners
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-generate trigger_code when creating new diagrams
        static::creating(function ($model) {
            if (empty($model->trigger_code)) {
                $model->trigger_code = static::generateUniqueTriggerCode($model->name);
            }

            // Set default category if not provided
            if (empty($model->category)) {
                $model->category = 'General';
            }

            // Set default icon if not provided
            if (empty($model->icon)) {
                $model->icon = 'WorkflowIcon';
            }
        });

        // No special handling needed for updates - versioning is handled in controller
    }

    /**
     * Create a new version of this diagram
     */
    public function createNewVersion($versionNotes = null)
    {
        // Generate a unique old version trigger code
        $baseOldCode = $this->trigger_code . '_OLD_V' . $this->version;
        $oldTriggerCode = $this->generateUniqueOldVersionCode($baseOldCode);

        // Mark current version as not latest and update its trigger code
        $this->update([
            'is_latest_version' => false,
            'trigger_code' => $oldTriggerCode
        ]);

        // Create new version with the original trigger code
        $newVersion = $this->replicate();
        $newVersion->version = $this->getNextVersionNumber();
        $newVersion->parent_diagram_id = $this->parent_diagram_id ?: $this->id;
        $newVersion->is_latest_version = true;
        $newVersion->version_notes = $versionNotes;
        $newVersion->trigger_code = $this->getOriginal('trigger_code'); // Keep original trigger code
        $newVersion->save();

        return $newVersion;
    }

    /**
     * Generate a unique trigger code for old versions
     */
    private function generateUniqueOldVersionCode($baseCode)
    {
        $counter = 1;
        $code = $baseCode;

        while (static::where('trigger_code', $code)->where('id', '!=', $this->id)->exists()) {
            $code = $baseCode . '_' . $counter;
            $counter++;

            // Prevent infinite loop
            if ($counter > 99) {
                $code = $baseCode . '_' . time();
                break;
            }
        }

        return $code;
    }

    /**
     * Get next version number for this diagram family
     */
    public function getNextVersionNumber()
    {
        $parentId = $this->parent_diagram_id ?: $this->id;

        return static::where('parent_diagram_id', $parentId)
                    ->orWhere('id', $parentId)
                    ->max('version') + 1;
    }

    /**
     * Get all versions of this diagram
     */
    public function getAllVersions()
    {
        $parentId = $this->parent_diagram_id ?: $this->id;

        return static::where('parent_diagram_id', $parentId)
                    ->orWhere('id', $parentId)
                    ->orderBy('version', 'desc')
                    ->get();
    }

    /**
     * Get the latest version of this diagram
     */
    public function getLatestVersion()
    {
        $parentId = $this->parent_diagram_id ?: $this->id;

        return static::where('parent_diagram_id', $parentId)
                    ->orWhere('id', $parentId)
                    ->where('is_latest_version', true)
                    ->first();
    }

    /**
     * Get parent diagram (original)
     */
    public function parentDiagram()
    {
        return $this->belongsTo(FlowDiagram::class, 'parent_diagram_id');
    }

    /**
     * Get child versions
     */
    public function versions()
    {
        return $this->hasMany(FlowDiagram::class, 'parent_diagram_id');
    }

    /**
     * Get simulation runs for this diagram
     */
    public function simulationRuns()
    {
        return $this->hasMany(FlowSimulationRun::class);
    }

    /**
     * Get the latest simulation run
     */
    public function getLatestSimulationRun()
    {
        return $this->simulationRuns()
                   ->orderBy('created_at', 'desc')
                   ->first();
    }

    /**
     * Scope to get only latest versions
     */
    public function scopeLatestVersions($query)
    {
        return $query->where('is_latest_version', true);
    }

    /**
     * Scope to get deletable diagrams
     */
    public function scopeDeletable($query)
    {
        return $query->where('is_deletable', true);
    }

    /**
     * Check if diagram can be deleted
     */
    public function canBeDeleted()
    {
        return $this->is_deletable;
    }

    /**
     * Generate a unique trigger code based on the name
     */
    public static function generateUniqueTriggerCode($name)
    {
        $baseCode = Str::slug($name, '_');
        $baseCode = Str::upper($baseCode);
        $baseCode = 'FLOW_' . Str::limit($baseCode, 45, '');

        $counter = 1;
        $code = $baseCode;

        while (static::where('trigger_code', $code)->exists()) {
            $code = $baseCode . '_' . $counter;
            $counter++;

            if ($counter > 999) {
                $code = $baseCode . '_' . Str::random(4);
                break;
            }
        }

        return $code;
    }

    /**
     * Get available icons list
     */
    public static function getAvailableIcons()
    {
        return static::$availableIcons;
    }

    /**
     * Scope for searching diagrams
     */
    public function scopeSearch($query, $search)
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'LIKE', "%{$search}%")
              ->orWhere('description', 'LIKE', "%{$search}%")
              ->orWhere('category', 'LIKE', "%{$search}%")
              ->orWhere('trigger_code', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Scope for filtering by category
     */
    public function scopeByCategory($query, $category)
    {
        if (empty($category)) {
            return $query;
        }

        return $query->where('category', $category);
    }

    /**
     * Get all available categories
     */
    public static function getCategories()
    {
        return static::distinct('category')
                    ->whereNotNull('category')
                    ->orderBy('category')
                    ->pluck('category')
                    ->toArray();
    }

    /**
     * Get execution logs for this diagram
     */
    public function executions()
    {
        return $this->hasMany(FlowExecution::class);
    }

    /**
     * Get the start nodes (nodes with no incoming edges)
     */
    public function getStartNodes()
    {
        $nodes = $this->getNodesArray();
        $edges = $this->getEdgesArray();
        $targetIds = collect($edges)->pluck('target');

        return collect($nodes)->filter(function ($node) use ($targetIds) {
            return !$targetIds->contains($node['id']);
        });
    }

    /**
     * Find a node by ID
     */
    public function findNode($nodeId)
    {
        return collect($this->getNodesArray())->firstWhere('id', $nodeId);
    }

    /**
     * Get outgoing edges for a node
     */
    public function getOutgoingEdges($nodeId)
    {
        return collect($this->getEdgesArray())->where('source', $nodeId);
    }

    /**
     * Get nodes as array (decode JSON string)
     */
    public function getNodesArray()
    {
        return is_string($this->nodes) ? json_decode($this->nodes, true) : $this->nodes;
    }

    /**
     * Get edges as array (decode JSON string)
     */
    public function getEdgesArray()
    {
        return is_string($this->edges) ? json_decode($this->edges, true) : $this->edges;
    }

    /**
     * Get node count for this diagram
     */
    public function getNodeCount()
    {
        $nodes = $this->getNodesArray();
        return is_array($nodes) ? count($nodes) : 0;
    }

    /**
     * Get edge count for this diagram
     */
    public function getEdgeCount()
    {
        $edges = $this->getEdgesArray();
        return is_array($edges) ? count($edges) : 0;
    }

    /**
     * Find diagram by trigger code (latest version only)
     */
    public static function findByTriggerCode($triggerCode)
    {
        return static::where('trigger_code', $triggerCode)
                    ->where('is_latest_version', true)
                    ->first();
    }
}
