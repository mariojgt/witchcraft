<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

// Assuming BaseNodeHandler is in the same namespace or properly imported
// use Mariojgt\Witchcraft\Services\NodeHandlers\BaseNodeHandler; // Uncomment if BaseNodeHandler is in a different namespace

class CommentHandler extends BaseNodeHandler
{
    /**
     * Handles the Comment Node logic.
     * This handler performs no operations and simply passes through,
     * serving as a placeholder to prevent compilation issues.
     *
     * @param array $node The node configuration data from the workflow.
     * @param array $variables Current workflow variables/data.
     * @return array A success response indicating the node was processed.
     */
    public function handle(array $node, array $variables): array
    {
        // Extracting customTitle and comment for logging/debugging if needed,
        // but no actual processing is done.
        $customTitle = $this->getData($node, 'customTitle', 'Comment Block');
        $commentText = $this->getData($node, 'comment', 'No comment provided.');

        // This handler does nothing functional; it just confirms its execution.
        return $this->success(
            [], // No specific output data needed for a comment node
            "Comment Node '{$customTitle}' processed (No action taken)."
        );
    }
}
