<?php

namespace Mariojgt\Witchcraft\Contracts;

interface NodeHandlerInterface
{
    /**
     * Handle the node processing
     */
    public function handle(array $node, array $variables): array;
}
