<?php

namespace Mariojgt\Witchcraft\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class NodeProcessor
{
    protected $variables = [];

    public function processNode($node, $inputData = [])
    {
        $this->variables = $inputData;

        return match ($node['type']) {
            'variable' => $this->processVariableNode($node),
            'api' => $this->processApiNode($node),
            'if' => $this->processConditionNode($node),
            'notification' => $this->processNotificationNode($node),
            'modelselect' => $this->processModelSelectNode($node),
            'parsejson' => $this->processJsonExtractNode($node),
            'artisan' => $this->processArtisanNode($node),
            default => throw new \Exception("Unknown node type: {$node['type']}")
        };
    }

    protected function processVariableNode($node)
    {
        $outputKey = $node['data']['outputKey'];
        $value = $node['data']['initialValue'];

        return [
            'success' => true,
            'output' => [$outputKey => $value],
            'message' => "Variable {$outputKey} set to {$value}"
        ];
    }

    protected function processApiNode($node)
    {
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->request($node['data']['method'], $node['data']['url'], [
                'headers' => json_decode($node['data']['headers'] ?? '{}', true),
                'json' => json_decode($node['data']['body'] ?? '{}', true)
            ]);

            $result = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'output' => $node['data']['saveResponse'] ? ['apiResponse' => $result] : [],
                'message' => "API call successful"
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => [],
                'message' => "API call failed: {$e->getMessage()}"
            ];
        }
    }

    protected function processConditionNode($node)
    {
        try {
            if (isset($this->variables['modelEvent'])) {
                $actualValue = $this->variables['extractedValue'];
            } else {
                // Get the first variable from the input data since variableName is not provided
                $actualValue = count($this->variables) > 0 ? reset($this->variables) : null;
            }
            // TODO handle this is a better way for different types of nodes
            $expectedValue = $node['data']['expectedValue'] ?? null;

            $result = match ($node['data']['conditionType']) {
                'equals' => $actualValue == $expectedValue,
                'notEquals' => $actualValue != $expectedValue,
                'contains' => is_string($actualValue) && is_string($expectedValue) ?
                    str_contains($actualValue, $expectedValue) : false,
                'changed' => $actualValue != ($node['data']['previousValue'] ?? null),
                default => false
            };

            return [
                'success' => true,
                'conditionResult' => $result,
                'output' => [
                    'result' => $result,
                    'actualValue' => $actualValue,
                    'expectedValue' => $expectedValue
                ],
                'message' => "Condition evaluated to: " . ($result ? 'true' : 'false')
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => [],
                'message' => "Condition evaluation failed: " . $e->getMessage()
            ];
        }
    }

    protected function processNotificationNode($node)
    {
        $message = preg_replace_callback('/\{\{(\w+)\}\}/', function ($matches) {
            return $this->variables[$matches[1]] ?? $matches[0];
        }, $node['data']['message']);

        // Here you would integrate with your notification system

        return [
            'success' => true,
            'output' => ['message' => $message],
            'message' => "Notification: {$message}"
        ];
    }

    protected function processModelSelectNode($node)
    {
        $modelName = $node['data']['modelName'];
        $eventType = $node['data']['eventType'];
        $watchFields = $node['data']['watchFields'];
        $outputKey = $node['data']['outputKey'];
        $testMode = $node['data']['testMode'] ?? false;

        // If test mode is enabled, fetch the first random record
        if ($testMode) {
            try {
                // Dynamically get the first record from the specified model
                $model = DB::table($modelName)->first();

                if (!$model) {
                    return [
                        'success' => false,
                        'output' => [],
                        'message' => "No test data found for model: {$modelName}"
                    ];
                }

                // Convert to array if it's an object
                $model = (array)$model;
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'output' => [],
                    'message' => "Error fetching test data: " . $e->getMessage()
                ];
            }
        } else {
            // Normal production mode
            $model = $this->variables['model'] ?? null;
        }

        // Validate model data
        if (!$model) {
            return [
                'success' => false,
                'output' => [],
                'message' => "No model found for event: {$modelName} - {$eventType}"
            ];
        }

        // Optional: Check watch fields
        if (!empty($watchFields)) {
            $changedFields = array_intersect(
                $watchFields,
                array_keys($model)
            );

            if (empty($changedFields)) {
                return [
                    'success' => false,
                    'output' => [],
                    'message' => "No watched fields found"
                ];
            }
        }

        return [
            'success' => true,
            'output' => [
                $outputKey => $model
            ],
            'message' => "Model event registered: {$modelName} - {$eventType}"
        ];
    }

    protected function processJsonExtractNode($node)
    {
        $sourceVariable = $node['data']['sourceVariable'];
        $jsonPath = $node['data']['jsonPath'];
        $outputKey = $node['data']['outputKey'];

        // Get the source JSON (model) from variables
        $sourceJson = $this->variables[$sourceVariable] ?? null;

        if (!$sourceJson) {
            return [
                'success' => false,
                'output' => [],
                'message' => "Source variable {$sourceVariable} not found"
            ];
        }

        // Extract value using the JSON path
        $extractedValue = $this->extractValueFromPath($sourceJson, $jsonPath);

        return [
            'success' => true,
            'output' => [
                $outputKey => $extractedValue
            ],
            'message' => "Extracted {$jsonPath} from {$sourceVariable}"
        ];
    }

    // Helper method to extract nested values
    protected function extractValueFromPath($data, $path)
    {
        $keys = explode('.', $path);
        $value = $data;

        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return $value;
    }

    protected function processArtisanNode($node)
    {
        try {
            // Prepare command and arguments
            $command = $node['data']['command'];
            $rawArguments = $node['data']['arguments'] ?? '';
            $saveOutput = $node['data']['saveOutput'] ?? true;
            $outputKey = $node['data']['outputKey'] ?? 'artisanOutput';

            // Construct full command line
            $fullCommandLine = "php artisan {$command}";

            // Parse arguments
            $arguments = [];
            if (!empty(trim($rawArguments))) {
                // Split arguments, trim each, and remove empty entries
                $argumentArray = array_filter(array_map('trim', explode("\n", $rawArguments)));

                // Add arguments to command line and to arguments array
                foreach ($argumentArray as $arg) {
                    $fullCommandLine .= " {$arg}";
                    $arguments[] = $arg;
                }
            }

            // Prepare output buffer
            $output = new BufferedOutput();

            // Run Artisan command
            $exitCode = Artisan::call($command, $arguments, $output);

            // Get command output
            $commandOutput = $output->fetch();

            // Prepare return data
            $result = [
                'success' => $exitCode === 0,
                'output' => $saveOutput ? [
                    $outputKey => $commandOutput,
                    'commandLine' => $fullCommandLine
                ] : [],
                'message' => "Artisan command '{$command}' executed. Exit code: {$exitCode}",
                'exitCode' => $exitCode,
                'commandLine' => $fullCommandLine
            ];

            return $result;
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => [],
                'message' => "Artisan command failed: " . $e->getMessage()
            ];
        }
    }
}
