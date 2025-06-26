<?php

namespace Mariojgt\Witchcraft\Traits;

use Mariojgt\Witchcraft\Models\FlowDiagram;
use Mariojgt\Witchcraft\Services\FlowExecutor;

trait HasWitchcraftTriggers
{
    protected static function bootHasWitchcraftTriggers()
    {
        static::created(fn($model) => static::triggerFlows($model, 'created'));
        static::updated(fn($model) => static::triggerFlows($model, 'updated'));
        static::deleted(fn($model) => static::triggerFlows($model, 'deleted'));
    }

    protected static function triggerFlows($model, $event)
    {
        $tableName = $model->getTable();

        // Find diagrams that should be triggered by this model event
        $diagrams = FlowDiagram::where('is_active', true)
            ->get()
            ->filter(function ($diagram) use ($tableName, $event) {
                $startNodes = $diagram->getStartNodes();

                return $startNodes->contains(function ($node) use ($tableName, $event) {
                    return $node['type'] === 'modelselect'
                        && ($node['data']['modelName'] ?? '') === $tableName
                        && ($node['data']['eventType'] ?? '') === $event;
                });
            });

        foreach ($diagrams as $diagram) {
            try {
                $executor = new FlowExecutor($diagram);
                $executor->run([
                    'model' => $model->toArray(),
                    'event' => $event,
                    'changes' => $model->getDirty(),
                ]);
            } catch (\Exception $e) {
                \Log::error("Flow execution failed for diagram {$diagram->id}: " . $e->getMessage());
            }
        }
    }
}
