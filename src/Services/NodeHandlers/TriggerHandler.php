<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class TriggerHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        $outputKey = $this->getData($node, 'outputKey', 'output');
        $initialValue = $this->getData($node, 'initialValue');

        $value = !empty($variables) ? reset($variables) : $initialValue;

        return $this->success([
            $outputKey => $value,
            'extractedValue' => $value
        ], "Trigger set {$outputKey} to {$value}");
    }
}
