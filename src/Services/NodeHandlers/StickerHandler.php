<?php
namespace Mariojgt\Witchcraft\Services\NodeHandlers;

// Assuming BaseNodeHandler is in the same namespace or properly imported
// use Mariojgt\Witchcraft\Services\NodeHandlers\BaseNodeHandler; // Uncomment if BaseNodeHandler is in a different namespace

class StickerHandler extends BaseNodeHandler
{
    /**
     * Handles the Sticker Node logic.
     * This handler performs no operations and simply passes through,
     * serving as a visual/decorative element to prevent compilation issues.
     *
     * @param array $node The node configuration data from the workflow.
     * @param array $variables Current workflow variables/data.
     * @return array A success response indicating the node was processed.
     */
    public function handle(array $node, array $variables): array
    {
        // This handler does nothing functional; it just confirms its execution.
        // Stickers are purely decorative elements in the workflow.
        return $this->success(
            [], // Return sticker configuration for potential use
            "Sticker Node with emoji"
        );
    }
}
