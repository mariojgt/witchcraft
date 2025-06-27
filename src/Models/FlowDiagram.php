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
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $attributes = [
        'is_active' => true
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

        // Update trigger_code when name changes (optional)
        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->getOriginal('trigger_code'))) {
                $model->trigger_code = static::generateUniqueTriggerCode($model->name);
            }
        });
    }

    /**
     * Generate a unique trigger code based on the name
     */
    public static function generateUniqueTriggerCode($name)
    {
        // Create base code from name
        $baseCode = Str::slug($name, '_');
        $baseCode = Str::upper($baseCode);

        // Limit to 50 characters and add prefix
        $baseCode = 'FLOW_' . Str::limit($baseCode, 45, '');

        // Ensure uniqueness
        $counter = 1;
        $code = $baseCode;

        while (static::where('trigger_code', $code)->exists()) {
            $code = $baseCode . '_' . $counter;
            $counter++;

            // Prevent infinite loop
            if ($counter > 999) {
                $code = $baseCode . '_' . Str::random(4);
                break;
            }
        }

        return $code;
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
     * Find diagram by trigger code
     */
    public static function findByTriggerCode($triggerCode)
    {
        return static::where('trigger_code', $triggerCode)->first();
    }
}
