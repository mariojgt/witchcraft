<?php

namespace Mariojgt\Witchcraft\Contracts;

interface NodeHandlerInterface
{
    /**
     * Handle the node processing
     *
     * @param array $nodeData The node data
     * @param array $variables Current workflow variables
     * @return array
     */
    public function handle(array $nodeData, array $variables): array;
}
