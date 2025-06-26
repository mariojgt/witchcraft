<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class SwitchHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        try {
            $switchExpression = $variables['extractedValue'] ?? null;
            $cases = $this->getData($node, 'cases', []);

            if (empty($cases)) {
                return $this->error('Switch cases are required');
            }

            // Find matching case
            $selectedCase = null;
            foreach ($cases as $index => $case) {
                // Skip default case (usually last one)
                if (isset($case['isDefault']) && $case['isDefault']) {
                    continue;
                }

                if ((string)($case['value'] ?? '') === (string)$switchExpression) {
                    $selectedCase = $index;
                    break;
                }
            }

            // If no case matches, use default case
            if ($selectedCase === null) {
                foreach ($cases as $index => $case) {
                    if (isset($case['isDefault']) && $case['isDefault']) {
                        $selectedCase = $index;
                        break;
                    }
                }
                // If no default case, use last case
                if ($selectedCase === null) {
                    $selectedCase = count($cases) - 1;
                }
            }

            $matchedCase = $cases[$selectedCase] ?? [];

            return $this->success([
                'switchValue' => $switchExpression,
                'selectedCase' => $selectedCase,
                'matchedValue' => $matchedCase['value'] ?? null,
                'extractedValue' => $switchExpression, // Add for edge routing
            ], sprintf(
                "Switch evaluated: %s matched case %s",
                $switchExpression,
                $matchedCase['value'] ?? 'default'
            )) + ['selectedCase' => $selectedCase ]; // Add for edge routing
        } catch (\Exception $e) {
            return $this->error("Switch evaluation failed: " . $e->getMessage());
        }
    }
}
