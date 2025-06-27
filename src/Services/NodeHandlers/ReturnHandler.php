<?php
namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class ReturnHandler extends BaseNodeHandler
{
    /**
     * Handles the return node logic.
     *
     * This node takes a specified variable from the incoming variables
     * and returns it as 'extractedValue' and 'returnValue' for further processing.
     *
     * @param array $node The node configuration from the workflow.
     * @param array $variables The variables passed from the previous node.
     * @return array The result of the node processing, including the returned value.
     */
    public function handle(array $node, array $variables): array
    {
        // Get the variable name to return from the node configuration.
        // It defaults to 'extractedValue' if not explicitly set in the node.
        $variableToReturn = $this->getData($node, 'variableToReturn', 'extractedValue');

        // Check if the specified variable exists in the incoming variables.
        if (!isset($variables[$variableToReturn])) {
            // If the variable isn't found, you might want to handle this as an error
            // or return null/default, depending on your workflow's robustness needs.
            return $this->error("Variable '{$variableToReturn}' not found in available variables.");
        }

        $actualValue = $variables[$variableToReturn];

        // The success method from BaseNodeHandler should be used.
        // We're returning the selected variable as both 'extractedValue'
        // (for general flow) and 'returnValue' (for explicit return semantics).
        return $this->success([
            'extractedValue' => $actualValue, // The main value passed to the next node
            'returnValue' => $actualValue,   // A specific key for the return value
        ], "Return node processed, returning variable '{$variableToReturn}': " . json_encode($actualValue));
    }
}
