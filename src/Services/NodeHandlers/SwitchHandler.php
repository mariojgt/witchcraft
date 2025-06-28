<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class SwitchHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        try {
            // ✨ ENHANCED: Get switch expression from multiple sources
            $switchExpression = $this->getSwitchValue($node, $variables);
            $cases = $this->getData($node, 'cases', []);
            $comparisonMode = $this->getData($node, 'comparisonMode', 'exact');
            $caseSensitive = $this->getData($node, 'caseSensitive', true);
            $useExtractedValue = $this->getData($node, 'useExtractedValue', true);

            if (empty($cases)) {
                return $this->error('Switch cases are required');
            }

            // ✨ NEW: Enhanced case matching with multiple comparison modes
            $matchResult = $this->findMatchingCase($switchExpression, $cases, $comparisonMode, $caseSensitive);

            if ($matchResult['error']) {
                return $this->error($matchResult['error']);
            }

            $selectedCase = $matchResult['index'];
            $matchedCase = $cases[$selectedCase] ?? [];
            $matchType = $matchResult['type'];

            // ✨ ENHANCED: Prepare comprehensive output
            $output = [
                'switchValue' => $switchExpression,
                'selectedCase' => $selectedCase,
                'matchedValue' => $matchedCase['value'] ?? null,
                'matchType' => $matchType,
                'comparisonMode' => $comparisonMode,
                'extractedValue' => $switchExpression, // For edge routing
                'caseCount' => count($cases),
                'isDefaultCase' => isset($matchedCase['isDefault']) && $matchedCase['isDefault']
            ];

            // ✨ NEW: Add case description if available
            if (!empty($matchedCase['description'])) {
                $output['caseDescription'] = $matchedCase['description'];
            }

            $message = $this->buildSuccessMessage($switchExpression, $matchedCase, $selectedCase, $matchType);

            return $this->success($output, $message) + ['selectedCase' => $selectedCase];

        } catch (\Exception $e) {
            return $this->error("Switch evaluation failed: " . $e->getMessage());
        }
    }

    /**
     * ✨ NEW: Get switch value from various sources
     */
    private function getSwitchValue(array $node, array $variables)
    {
        $useExtractedValue = $this->getData($node, 'useExtractedValue', true);

        if ($useExtractedValue) {
            return $variables['extractedValue'] ?? null;
        }

        $switchExpression = $this->getData($node, 'switchExpression', '');

        // Replace variables in the expression
        if (is_string($switchExpression)) {
            return $this->replaceVariables($switchExpression, $variables);
        }

        return $switchExpression;
    }

    /**
     * ✨ ENHANCED: Find matching case with multiple comparison modes
     */
    private function findMatchingCase($switchValue, array $cases, string $comparisonMode, bool $caseSensitive): array
    {
        $result = [
            'index' => null,
            'type' => 'none',
            'error' => null
        ];

        // Convert switch value to string for comparison
        $switchValueStr = $this->convertToString($switchValue);

        // First pass: Look for exact matches (excluding default cases)
        foreach ($cases as $index => $case) {
            if (isset($case['isDefault']) && $case['isDefault']) {
                continue;
            }

            $caseValue = $case['value'] ?? '';

            if ($this->performComparison($switchValueStr, $caseValue, $comparisonMode, $caseSensitive)) {
                $result['index'] = $index;
                $result['type'] = $comparisonMode;
                return $result;
            }
        }

        // Second pass: Look for default case
        foreach ($cases as $index => $case) {
            if (isset($case['isDefault']) && $case['isDefault']) {
                $result['index'] = $index;
                $result['type'] = 'default';
                return $result;
            }
        }

        // Fallback: Use last case if no default found
        if (!empty($cases)) {
            $result['index'] = count($cases) - 1;
            $result['type'] = 'fallback';
            return $result;
        }

        $result['error'] = 'No cases available for matching';
        return $result;
    }

    /**
     * ✨ NEW: Perform comparison based on mode
     */
    private function performComparison($switchValue, $caseValue, string $mode, bool $caseSensitive): bool
    {
        if (empty($caseValue)) {
            return false;
        }

        // Apply case sensitivity
        $switchVal = $caseSensitive ? $switchValue : strtolower($switchValue);
        $caseVal = $caseSensitive ? $caseValue : strtolower($caseValue);

        switch ($mode) {
            case 'exact':
                return $switchVal === $caseVal;

            case 'loose':
                return $switchVal == $caseVal;

            case 'contains':
                return strpos($switchVal, $caseVal) !== false;

            case 'regex':
                try {
                    return preg_match('/' . $caseValue . '/', $switchValue) === 1;
                } catch (\Exception $e) {
                    // Invalid regex, fall back to exact match
                    return $switchVal === $caseVal;
                }

            case 'range':
                return $this->performRangeComparison($switchValue, $caseValue);

            default:
                return $switchVal === $caseVal;
        }
    }

    /**
     * ✨ NEW: Handle numeric range comparisons
     */
    private function performRangeComparison($switchValue, $rangeValue): bool
    {
        if (!is_numeric($switchValue)) {
            return false;
        }

        // Parse range format: "min-max"
        if (!preg_match('/^(\d+(?:\.\d+)?)-(\d+(?:\.\d+)?)$/', $rangeValue, $matches)) {
            return false;
        }

        $min = (float) $matches[1];
        $max = (float) $matches[2];
        $value = (float) $switchValue;

        return $value >= $min && $value <= $max;
    }

    /**
     * ✨ NEW: Build detailed success message
     */
    private function buildSuccessMessage($switchValue, array $matchedCase, int $selectedCase, string $matchType): string
    {
        $valueStr = $this->convertToString($switchValue);
        $caseStr = $matchedCase['value'] ?? 'unknown';

        $typeDescriptions = [
            'exact' => 'exactly matched',
            'loose' => 'loosely matched',
            'contains' => 'contained in',
            'regex' => 'matched pattern',
            'range' => 'fell within range',
            'default' => 'fell through to default',
            'fallback' => 'used fallback'
        ];

        $typeDesc = $typeDescriptions[$matchType] ?? 'matched';

        if ($matchType === 'default') {
            return "Switch value '{$valueStr}' {$typeDesc} case";
        }

        return "Switch value '{$valueStr}' {$typeDesc} case '{$caseStr}' (index {$selectedCase})";
    }

    /**
     * ✨ NEW: Convert value to string safely
     */
    private function convertToString($value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return '';
        }

        return (string) $value;
    }

    /**
     * ✨ NEW: Validate switch configuration
     */
    public function validateSwitchConfiguration(array $node): array
    {
        $cases = $this->getData($node, 'cases', []);
        $comparisonMode = $this->getData($node, 'comparisonMode', 'exact');
        $useExtractedValue = $this->getData($node, 'useExtractedValue', true);
        $switchExpression = $this->getData($node, 'switchExpression', '');

        $errors = [];
        $warnings = [];

        // Check if we have any cases
        if (empty($cases)) {
            $errors[] = 'No cases defined';
        }

        // Check for input source
        if (!$useExtractedValue && empty($switchExpression)) {
            $warnings[] = 'No switch expression defined and not using extracted value';
        }

        // Validate individual cases
        $hasDefault = false;
        $nonDefaultCases = 0;
        $duplicateValues = [];
        $caseValues = [];

        foreach ($cases as $index => $case) {
            if (isset($case['isDefault']) && $case['isDefault']) {
                $hasDefault = true;
                continue;
            }

            $nonDefaultCases++;
            $value = $case['value'] ?? '';

            if (empty($value)) {
                $warnings[] = "Case {$index} has no value";
                continue;
            }

            // Check for duplicates
            if (in_array($value, $caseValues)) {
                $duplicateValues[] = $value;
            } else {
                $caseValues[] = $value;
            }

            // Validate based on comparison mode
            if ($comparisonMode === 'regex') {
                if (@preg_match('/' . $value . '/', '') === false) {
                    $errors[] = "Case {$index} has invalid regex pattern: {$value}";
                }
            } elseif ($comparisonMode === 'range') {
                if (!preg_match('/^\d+(?:\.\d+)?-\d+(?:\.\d+)?$/', $value)) {
                    $errors[] = "Case {$index} has invalid range format: {$value} (should be 'min-max')";
                }
            }
        }

        // Check for duplicate values
        if (!empty($duplicateValues)) {
            $warnings[] = 'Duplicate case values found: ' . implode(', ', array_unique($duplicateValues));
        }

        // Check if we have at least one non-default case
        if ($nonDefaultCases === 0) {
            $warnings[] = 'No non-default cases defined';
        }

        // Check for default case
        if (!$hasDefault) {
            $warnings[] = 'No default case defined - last case will be used as fallback';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'stats' => [
                'total_cases' => count($cases),
                'non_default_cases' => $nonDefaultCases,
                'has_default' => $hasDefault,
                'comparison_mode' => $comparisonMode,
                'use_extracted_value' => $useExtractedValue
            ]
        ];
    }

    /**
     * ✨ NEW: Get debug information about switch execution
     */
    public function debugSwitchExecution(array $node, array $variables): array
    {
        $switchValue = $this->getSwitchValue($node, $variables);
        $cases = $this->getData($node, 'cases', []);
        $comparisonMode = $this->getData($node, 'comparisonMode', 'exact');
        $caseSensitive = $this->getData($node, 'caseSensitive', true);

        $debug = [
            'switch_value' => $switchValue,
            'switch_value_type' => gettype($switchValue),
            'switch_value_string' => $this->convertToString($switchValue),
            'comparison_mode' => $comparisonMode,
            'case_sensitive' => $caseSensitive,
            'total_cases' => count($cases),
            'case_evaluations' => []
        ];

        // Test each case
        $switchValueStr = $this->convertToString($switchValue);

        foreach ($cases as $index => $case) {
            $caseValue = $case['value'] ?? '';
            $isDefault = isset($case['isDefault']) && $case['isDefault'];

            $evaluation = [
                'index' => $index,
                'value' => $caseValue,
                'is_default' => $isDefault,
                'matches' => false,
                'comparison_details' => []
            ];

            if (!$isDefault && !empty($caseValue)) {
                $evaluation['matches'] = $this->performComparison(
                    $switchValueStr,
                    $caseValue,
                    $comparisonMode,
                    $caseSensitive
                );

                // Add detailed comparison info
                $evaluation['comparison_details'] = [
                    'switch_value_processed' => $caseSensitive ? $switchValueStr : strtolower($switchValueStr),
                    'case_value_processed' => $caseSensitive ? $caseValue : strtolower($caseValue),
                    'mode' => $comparisonMode
                ];

                if ($comparisonMode === 'range') {
                    $evaluation['comparison_details']['range_valid'] = preg_match('/^\d+(?:\.\d+)?-\d+(?:\.\d+)?$/', $caseValue);
                } elseif ($comparisonMode === 'regex') {
                    $evaluation['comparison_details']['regex_valid'] = @preg_match('/' . $caseValue . '/', '') !== false;
                }
            }

            $debug['case_evaluations'][] = $evaluation;
        }

        // Find the actual match
        $matchResult = $this->findMatchingCase($switchValue, $cases, $comparisonMode, $caseSensitive);
        $debug['match_result'] = $matchResult;

        return $debug;
    }

    /**
     * ✨ NEW: Test switch with sample values
     */
    public function testSwitchWithSamples(array $node, array $sampleValues): array
    {
        $results = [];

        foreach ($sampleValues as $sampleValue) {
            $testVariables = ['extractedValue' => $sampleValue];
            $result = $this->handle($node, $testVariables);

            $results[] = [
                'input' => $sampleValue,
                'input_type' => gettype($sampleValue),
                'success' => $result['success'] ?? false,
                'selected_case' => $result['data']['selectedCase'] ?? null,
                'matched_value' => $result['data']['matchedValue'] ?? null,
                'match_type' => $result['data']['matchType'] ?? null,
                'message' => $result['message'] ?? '',
                'error' => $result['success'] ? null : ($result['message'] ?? 'Unknown error')
            ];
        }

        return [
            'test_count' => count($sampleValues),
            'results' => $results,
            'summary' => [
                'successful_matches' => count(array_filter($results, fn($r) => $r['success'])),
                'failed_matches' => count(array_filter($results, fn($r) => !$r['success'])),
                'default_matches' => count(array_filter($results, fn($r) => ($r['match_type'] ?? '') === 'default')),
                'exact_matches' => count(array_filter($results, fn($r) => ($r['match_type'] ?? '') === 'exact'))
            ]
        ];
    }

    /**
     * ✨ NEW: Generate sample test cases based on switch configuration
     */
    public function generateTestCases(array $node): array
    {
        $cases = $this->getData($node, 'cases', []);
        $comparisonMode = $this->getData($node, 'comparisonMode', 'exact');

        $testCases = [];

        foreach ($cases as $index => $case) {
            if (isset($case['isDefault']) && $case['isDefault']) {
                continue;
            }

            $caseValue = $case['value'] ?? '';
            if (empty($caseValue)) {
                continue;
            }

            // Generate test cases based on comparison mode
            switch ($comparisonMode) {
                case 'exact':
                case 'loose':
                    $testCases[] = [
                        'value' => $caseValue,
                        'description' => "Exact match for case {$index}",
                        'expected_case' => $index
                    ];
                    break;

                case 'contains':
                    $testCases[] = [
                        'value' => "prefix_{$caseValue}_suffix",
                        'description' => "Contains test for case {$index}",
                        'expected_case' => $index
                    ];
                    break;

                case 'range':
                    if (preg_match('/^(\d+(?:\.\d+)?)-(\d+(?:\.\d+)?)$/', $caseValue, $matches)) {
                        $min = (float) $matches[1];
                        $max = (float) $matches[2];
                        $mid = ($min + $max) / 2;

                        $testCases[] = [
                            'value' => $mid,
                            'description' => "Range middle value for case {$index}",
                            'expected_case' => $index
                        ];
                        $testCases[] = [
                            'value' => $min,
                            'description' => "Range minimum value for case {$index}",
                            'expected_case' => $index
                        ];
                        $testCases[] = [
                            'value' => $max,
                            'description' => "Range maximum value for case {$index}",
                            'expected_case' => $index
                        ];
                    }
                    break;

                case 'regex':
                    // For regex, just test the pattern itself
                    $testCases[] = [
                        'value' => $caseValue,
                        'description' => "Regex pattern test for case {$index}",
                        'expected_case' => $index
                    ];
                    break;
            }
        }

        // Add some edge cases
        $edgeCases = [
            ['value' => '', 'description' => 'Empty string', 'expected_case' => 'default'],
            ['value' => null, 'description' => 'Null value', 'expected_case' => 'default'],
            ['value' => 0, 'description' => 'Zero', 'expected_case' => 'varies'],
            ['value' => false, 'description' => 'Boolean false', 'expected_case' => 'varies'],
            ['value' => 'nonexistent_value_12345', 'description' => 'Non-matching string', 'expected_case' => 'default']
        ];

        $testCases = array_merge($testCases, $edgeCases);

        return [
            'test_cases' => $testCases,
            'total_cases' => count($testCases),
            'comparison_mode' => $comparisonMode,
            'notes' => [
                'Edge cases with expected_case "default" should match the default case',
                'Edge cases with expected_case "varies" depend on the specific case values',
                'Test cases are generated based on the current comparison mode'
            ]
        ];
    }

    /**
     * ✨ NEW: Optimize switch case order for better performance
     */
    public function optimizeCaseOrder(array $node, array $usageStats = []): array
    {
        $cases = $this->getData($node, 'cases', []);

        if (empty($usageStats)) {
            return [
                'optimized' => false,
                'message' => 'No usage statistics provided for optimization',
                'original_order' => $cases
            ];
        }

        // Separate default case
        $defaultCase = null;
        $defaultIndex = null;
        $regularCases = [];

        foreach ($cases as $index => $case) {
            if (isset($case['isDefault']) && $case['isDefault']) {
                $defaultCase = $case;
                $defaultIndex = $index;
            } else {
                $regularCases[] = ['case' => $case, 'original_index' => $index];
            }
        }

        // Sort regular cases by usage frequency (most used first)
        usort($regularCases, function($a, $b) use ($usageStats) {
            $usageA = $usageStats[$a['original_index']] ?? 0;
            $usageB = $usageStats[$b['original_index']] ?? 0;
            return $usageB - $usageA; // Descending order
        });

        // Rebuild optimized cases array
        $optimizedCases = [];
        foreach ($regularCases as $item) {
            $optimizedCases[] = $item['case'];
        }

        // Add default case at the end
        if ($defaultCase) {
            $optimizedCases[] = $defaultCase;
        }

        return [
            'optimized' => true,
            'original_order' => $cases,
            'optimized_order' => $optimizedCases,
            'optimization_applied' => count($regularCases) > 1,
            'performance_notes' => [
                'Cases are ordered by usage frequency (most frequent first)',
                'This reduces average comparison time for frequently matched cases',
                'Default case remains at the end as expected'
            ]
        ];
    }
}
