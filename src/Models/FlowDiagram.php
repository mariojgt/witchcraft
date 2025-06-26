<?php

namespace Mariojgt\Witchcraft\Models;

use Illuminate\Database\Eloquent\Model;

class FlowDiagram extends Model
{
    protected $fillable = [
        'name',
        'description',
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
}
