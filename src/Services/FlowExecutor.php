<?php

namespace Mariojgt\Witchcraft\Services;

use Mariojgt\Witchcraft\Models\FlowDiagram;
use Mariojgt\Witchcraft\Services\NodeHandlers\NodeHandlerFactory;

class FlowExecutor
{
    protected $diagram;
    protected $variables = [];
    protected $nodeStatuses = [];
    protected $executionLog = [];

    public function __construct(FlowDiagram $diagram = null)
    {
        $this->diagram = $diagram;
    }

    /**
     * Run the entire flow
     */
    public function run(array $initialVariables = [])
    {
        if (!$this->diagram) {
            throw new \Exception('No diagram provided');
        }

        $this->variables = $initialVariables;
        $this->log('Flow execution started');

        $startNodes = $this->diagram->getStartNodes();

        if ($startNodes->isEmpty()) {
            throw new \Exception('No start nodes found');
        }

        foreach ($startNodes as $node) {
            $this->executeNode($node);
        }

        $this->log('Flow execution completed');

        return [
            'variables' => $this->variables,
            'nodeStatuses' => $this->nodeStatuses,
            'executionLog' => $this->executionLog
        ];
    }

    /**
     * Execute a single node
     */
    protected function executeNode($node)
    {
        try {
            $this->setNodeStatus($node['id'], 'processing');
            $this->log("Processing node: {$node['id']} ({$node['type']})");

            $result = $this->processNode($node, $this->variables);

            if ($result['success']) {
                // Merge output variables
                if (!empty($result['output'])) {
                    $this->variables = array_merge($this->variables, $result['output']);
                }

                $this->setNodeStatus($node['id'], 'completed');
                $this->log("Node completed: {$node['id']} - {$result['message']}");

                // Process next nodes
                $this->processNextNodes($node, $result);
            } else {
                $this->setNodeStatus($node['id'], 'error');
                $this->log("Node failed: {$node['id']} - {$result['message']}", 'error');
                throw new \Exception($result['message']);
            }
        } catch (\Exception $e) {
            $this->setNodeStatus($node['id'], 'error');
            $this->log("Node error: {$node['id']} - {$e->getMessage()}", 'error');
            throw $e;
        }
    }

    /**
     * Process next nodes based on current node result
     */
    protected function processNextNodes($node, $result)
    {
        $outgoingEdges = $this->diagram->getOutgoingEdges($node['id']);

        foreach ($outgoingEdges as $edge) {
            $shouldFollow = true;

            // Handle conditional nodes
            if (isset($result['conditionResult'])) {
                $shouldFollow = $edge['sourceHandle'] === ($result['conditionResult'] ? 'true' : 'false');
            }

            if (isset($result['selectedCase'])) {
                $shouldFollow = (int)$edge['sourceHandle'] === ($result['selectedCase'] ?? null);
            }

            if ($shouldFollow) {
                $nextNode = $this->diagram->findNode($edge['target']);
                if ($nextNode) {
                    $this->executeNode($nextNode);
                }
            }
        }
    }

    /**
     * Process a single node using appropriate handler
     */
    public function processNode($node, $variables = [])
    {
        $handler = NodeHandlerFactory::create($node['type']);
        return $handler->handle($node, $variables);
    }

    /**
     * Set node execution status
     */
    protected function setNodeStatus($nodeId, $status)
    {
        $this->nodeStatuses[$nodeId] = $status;
    }

    /**
     * Log execution messages
     */
    protected function log($message, $level = 'info')
    {
        $this->executionLog[] = [
            'timestamp' => now()->toISOString(),
            'level' => $level,
            'message' => $message
        ];
    }
}
