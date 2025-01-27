<?php

namespace Mariojgt\Witchcraft\Services;

use Mariojgt\Witchcraft\Models\FlowDiagram;

class WitchcraftTrigger
{
    protected $diagram;
    protected $variables = [];

    public static function execute($diagramId, $variables = [])
    {
        $instance = new static();
        return $instance->trigger($diagramId, $variables);
    }

    protected function trigger($diagramId, $variables)
    {
        $this->diagram = FlowDiagram::find($diagramId);

        if (!$this->diagram) {
            throw new \Exception("Diagram not found");
        }

        $this->variables = $variables;

        $simulator = new DiagramSimulator($this->diagram, $this->variables);
        return $simulator->execute();
    }
}
