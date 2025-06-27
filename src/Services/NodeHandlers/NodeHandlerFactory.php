<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class NodeHandlerFactory
{
    protected static $handlers = [
        'trigger' => TriggerHandler::class,
        'api' => ApiHandler::class,
        'if' => ConditionHandler::class,
        'notification' => NotificationHandler::class,
        'modelselect' => ModelSelectHandler::class,
        'parsejson' => JsonExtractHandler::class,
        'artisan' => ArtisanHandler::class,
        'switchcase' => SwitchHandler::class,
        'triggerwebhook' => TriggerWebhookHandler::class,
        'setvariable' => SetVariableHandler::class,
        'getvariable' => GetVariableHandler::class,
        'triggerflow' => TriggerFlowHandler::class,
        'modellookup' => ModelLookupHandler::class,
        'datecondition' => DateConditionHandler::class,
        'return' => ReturnHandler::class,
    ];

    public static function create($nodeType)
    {
        // First try custom handler
        $customHandler = self::getCustomHandler($nodeType);

        if ($customHandler && class_exists($customHandler)) {
            return new $customHandler();
        }

        // Use built-in handler
        $handlerClass = self::$handlers[$nodeType] ?? null;

        if (!$handlerClass || !class_exists($handlerClass)) {
            throw new \Exception("No handler found for node type: {$nodeType}");
        }

        return new $handlerClass();
    }

    protected static function getCustomHandler($nodeType)
    {
        $handlerName = str_replace(['-', '_'], ' ', $nodeType);
        // Convert to CamelCase using laravel's Str helper
        $handlerName = \Illuminate\Support\Str::studly($handlerName);
        $handlerName = str_replace(' ', '', $handlerName);

        return "App\\Witchcraft\\Handlers\\{$handlerName}Handler";
    }

    public static function registerHandler($nodeType, $handlerClass)
    {
        self::$handlers[$nodeType] = $handlerClass;
    }
}
