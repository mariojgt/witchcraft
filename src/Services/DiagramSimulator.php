<?php

namespace Mariojgt\Witchcraft\Services;

use Mariojgt\Witchcraft\Models\FlowDiagram;

class DiagramSimulator
{
    protected $diagram;
    protected $variables = [];
    protected $nodeStatuses = [];
    protected $logger;
    protected $triggerType;

    public function __construct(FlowDiagram $diagram, array $variables = [], $triggerType = 'manual')
    {
        $this->diagram = $diagram;
        $this->variables = $variables;
        $this->triggerType = $triggerType;
        $this->logger = new FlowLogger();
    }

    public function execute()
    {
        try {
            // Start execution logging
            $this->logger->startExecution(
                $this->diagram->id,
                $this->triggerType,
                $this->variables
            );

            $nodes = collect($this->diagram->nodes);
            $edges = collect($this->diagram->edges);

            // Find start nodes
            $startNodes = $nodes->filter(function ($node) use ($edges) {
                return !$edges->contains('target', $node['id']);
            });

            foreach ($startNodes as $startNode) {
                $this->processNode($startNode, $nodes, $edges);
            }

            $this->logger->completeExecution();

            return [
                'success' => true,
                'variables' => $this->variables
            ];
        } catch (\Exception $e) {
            $this->logger->failExecution($e->getMessage());
            throw $e;
        }
    }

    protected function processNode($node, $nodes, $edges)
    {
        try {
            // Start node execution logging
            $this->logger->startNode(
                $node['id'],
                $node['type'],
                ['variables' => $this->variables]
            );

            $processor = new NodeProcessor();
            $result = $processor->processNode($node, $this->variables);

            if ($result['success']) {
                // Update variables with node output
                if (!empty($result['output'])) {
                    $this->variables = array_merge($this->variables, $result['output']);
                }

                // Log node completion
                $this->logger->completeNode($result['output']);

                // Process next nodes
                $outgoingEdges = $edges->filter(fn($edge) => $edge['source'] === $node['id']);

                foreach ($outgoingEdges as $edge) {
                    if ($node['type'] === 'if') {
                        $shouldFollow = $edge['sourceHandle'] === ($result['conditionResult'] ? 'true' : 'false');
                        if (!$shouldFollow) {
                            continue;
                        }
                    }

                    $nextNode = $nodes->firstWhere('id', $edge['target']);
                    if ($nextNode) {
                        $this->processNode($nextNode, $nodes, $edges);
                    }
                }
            } else {
                $this->logger->failNode($result['message']);
                throw new \Exception($result['message']);
            }
        } catch (\Exception $e) {
            $this->logger->failNode($e->getMessage());
            throw $e;
        }
    }
}
