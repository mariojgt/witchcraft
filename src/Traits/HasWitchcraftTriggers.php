<?php
namespace Mariojgt\Witchcraft\Traits;

use Mariojgt\Witchcraft\Models\FlowDiagram;
use Mariojgt\Witchcraft\Services\WitchcraftTrigger;

trait HasWitchcraftTriggers
{
    protected static function bootHasWitchcraftTriggers()
    {
        static::created(function ($model) {
            static::handleModelEvent($model, 'created');
        });

        static::updated(function ($model) {
            static::handleModelEvent($model, 'updated');
        });

        static::deleted(function ($model) {
            static::handleModelEvent($model, 'deleted');
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                static::handleModelEvent($model, 'restored');
            });
        }
    }

    protected static function handleModelEvent($model, $event)
    {
        $modelTableName = $model->getTable();
        $flowDiagram = FlowDiagram::all();
        $diagrams = [];
        foreach ($flowDiagram as $key => $diagram) {
            if ($diagram->nodes[0]['type'] === 'modelselect' && $diagram->nodes[0]['data']['modelName'] === $modelTableName && $diagram->nodes[0]['data']['eventType'] === $event) {
                $diagrams[] = $diagram;
                break;
            }
        }
        foreach ($diagrams as $diagram) {
            $modelNode = $diagram->nodes[0];

            if (!$modelNode) {
                continue;
            }

            // Check if we need to watch specific fields
            if (!empty($modelNode['data']['watchFields'])) {
                $changedFields = array_keys($model->getDirty());
                if (!array_intersect($changedFields, $modelNode['data']['watchFields'])) {
                    continue;
                }
            }

            // Execute the diagram with model data
            WitchcraftTrigger::execute($diagram->id, [
                'model' => $model->toArray(),
                'event' => $event,
                'changes' => $model->getDirty(),
                'watchFields' => $modelNode['data']['watchFields'] ?? [],
            ]);
        }
    }
}
