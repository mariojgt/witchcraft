<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

use Mariojgt\Witchcraft\Contracts\NodeHandlerInterface;

abstract class BaseNodeHandler implements NodeHandlerInterface
{
    /**
     * Create successful result
     */
    protected function success($output = [], $message = 'Node processed successfully')
    {
        return [
            'success' => true,
            'output' => $output,
            'message' => $message
        ];
    }

    /**
     * Create error result
     */
    protected function error($message = 'Node processing failed', $output = [])
    {
        return [
            'success' => false,
            'output' => $output,
            'message' => $message
        ];
    }

    /**
     * Get node data with fallback
     */
    protected function getData($node, $key, $default = null)
    {
        return $node['data'][$key] ?? $default;
    }

    /**
     * Replace variables in string
     */
    protected function replaceVariables($string, $variables)
    {
        return preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) use ($variables) {
            return $variables[$matches[1]] ?? $matches[0];
        }, $string);
    }
}
