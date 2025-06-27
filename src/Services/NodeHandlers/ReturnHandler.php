<?php
namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class ReturnHandler extends BaseNodeHandler
{
    /**
     * Handles the return node logic.
     *
     * This node takes a specified variable from the incoming variables
     * and returns it as 'extractedValue' and a customizable return variable name
     * for further processing.
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

        // Get the custom return variable name from the node configuration.
        // It defaults to 'returnValue' if not explicitly set in the node.
        $returnVariableName = $this->getData($node, 'returnVariableName', 'returnValue');

        // Validate that the return variable name is a valid identifier
        if (!$this->isValidVariableName($returnVariableName)) {
            return $this->error("Invalid return variable name '{$returnVariableName}'. Must be a valid identifier.");
        }

        // Check if the specified variable exists in the incoming variables.
        if (!isset($variables[$variableToReturn])) {
            // If the variable isn't found, you might want to handle this as an error
            // or return null/default, depending on your workflow's robustness needs.
            return $this->error("Variable '{$variableToReturn}' not found in available variables.");
        }

        $actualValue = $variables[$variableToReturn];

        // The success method from BaseNodeHandler should be used.
        // We're returning the selected variable as both 'extractedValue'
        // (for general flow) and the custom return variable name (for explicit return semantics).
        return $this->success([
            'extractedValue' => $actualValue, // The main value passed to the next node
            $returnVariableName => $actualValue, // The custom return variable name
        ], "Return node processed, returning variable '{$variableToReturn}' as '{$returnVariableName}': " . json_encode($actualValue));
    }

    /**
     * Validates that a variable name is a valid PHP identifier.
     *
     * @param string $name The variable name to validate
     * @return bool True if valid, false otherwise
     */
    private function isValidVariableName(string $name): bool
    {
        // Check if the name matches PHP variable naming rules:
        // - Must start with a letter or underscore
        // - Can contain letters, numbers, and underscores
        // - Cannot be empty
        return !empty($name) && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name);
    }
}
