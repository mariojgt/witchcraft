<?php

namespace Mariojgt\Witchcraft\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Output\BufferedOutput;

class NodeProcessor
{
    protected $variables = [];

    public function processNode($node, $inputData = null)
    {
        $this->variables = $inputData;

        // First, try to find a custom handler
        $handlerClass = $this->getCustomNodeHandler($node['type']);

        if ($handlerClass && class_exists($handlerClass)) {
            // Process using custom handler
            $handler = new $handlerClass();
            return $handler->handle($node['data'], $this->variables);
        }

        // If no custom handler found, process using default handlers
        return match ($node['type']) {
            'trigger' => $this->processTriggerNode($node, $inputData),
            'api' => $this->processApiNode($node),
            'if' => $this->processConditionNode($node),
            'switchcase' => $this->processSwitchNode($node),
            'notification' => $this->processNotificationNode($node),
            'modelselect' => $this->processModelSelectNode($node),
            'parsejson' => $this->processJsonExtractNode($node),
            'artisan' => $this->processArtisanNode($node),
            default => throw new \Exception("Unknown node type: {$node['type']}")
        };
    }

    protected function getCustomNodeHandler($type)
    {
        // Convert kebab-case or any other format to StudlyCase
        $handlerName = str_replace(['-', '_'], ' ', $type);
        $handlerName = ucwords($handlerName);
        $handlerName = str_replace(' ', '', $handlerName);

        // Try both namespaces (for flexibility)
        $possibleHandlers = [
            "App\\Witchcraft\\Handlers\\{$handlerName}Handler",
            "App\\Witchcraft\\Handlers\\{$handlerName}NodeHandler"
        ];

        foreach ($possibleHandlers as $handler) {
            if (class_exists($handler)) {
                return $handler;
            }
        }

        return null;
    }

    protected function processTriggerNode($node, $inputData)
    {
        $outputKey = $node['data']['outputKey'];
        if (empty(reset($inputData))) {
            $value = $node['data']['initialValue'] ?? null;
        } else {
            $value = reset($inputData);
        }

        return [
            'success' => true,
            'output' => [
                $outputKey => $value,
                'extractedValue' => $value
            ],
            'message' => "Variable {$outputKey} set to {$value}"
        ];
    }

    protected function processApiNode($node)
    {
        try {
            // Prepare request details
            $requestType = $node['data']['requestType'] ?? 'external';
            $method = $node['data']['method'];
            $url = $node['data']['url'];
            $saveResponse = $node['data']['saveResponse'] ?? true;
            $authenticatedRequest = $node['data']['authenticatedRequest'] ?? false;

            // Parse parameters and body
            $params = json_decode($node['data']['params'] ?? '{}', true);
            $body = json_decode($node['data']['body'] ?? '{}', true);
            $headers = json_decode($node['data']['headers'] ?? '{}', true);

            // Prepare request options
            $requestOptions = [
                'headers' => $headers,
                'verify' => false // Disable SSL verification (use cautiously)
            ];

            // Add body for methods that support it
            if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
                $requestOptions['json'] = $body;
            }

            // Handle authenticated requests
            if ($authenticatedRequest) {
                $headers['Authorization'] = 'Bearer ' . auth()->tokenById(auth()->id());
                $requestOptions['headers'] = $headers;
            }

            // Handle request types
            if ($requestType === 'internal') {
                // Internal route handling
                try {
                    // Use Laravel's route resolution
                    $routeParams = array_merge($params, $body);
                    $response = $this->executeInternalRoute($url, $method, $routeParams);

                    $result = $response instanceof JsonResponse
                        ? $response->getData(true)
                        : json_decode($response->getContent(), true);
                } catch (\Exception $e) {
                    throw new \Exception("Internal route error: " . $e->getMessage());
                }
            } else {
                // External API request
                $client = new \GuzzleHttp\Client();

                // Add query parameters for GET requests
                if ($method === 'GET' && !empty($params)) {
                    $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
                }

                $response = $client->request($method, $url, $requestOptions);
                $result = json_decode($response->getBody(), true);
            }

            // Prepare output
            return [
                'success' => true,
                'output' => $saveResponse ? ['apiResponse' => $result] : [],
                'message' => "API {$requestType} request successful",
                'requestDetails' => [
                    'method' => $method,
                    'url' => $url,
                    'params' => $params,
                    'body' => $body
                ]
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => [],
                'message' => "API request failed: " . $e->getMessage(),
                'error' => [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]
            ];
        }
    }

    // Helper method to execute internal routes
    protected function executeInternalRoute($routeName, $method, $params)
    {
        // Resolve the route
        $route = Route::getRoutes()->getByName($routeName);

        if (!$route) {
            throw new \Exception("Route not found: {$routeName}");
        }

        // Create a request
        $request = Request::create(
            $route->uri(),
            strtoupper($method),
            $params
        );

        // Dispatch the request through the kernel
        $response = app()->handle($request);

        return $response;
    }

    protected function processConditionNode($node)
    {
        try {
            $actualValue = $this->variables['extractedValue'] ?? null;

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

    protected function processSwitchNode($node)
    {
        try {
            $switchExpression = $this->variables['extractedValue'] ?? null;
            $cases = $node['data']['cases'];

            // Find matching case
            $selectedCase = null;
            foreach ($cases as $index => $case) {
                // Skip default case (last one)
                if ($index === count($cases) - 1) {
                    continue;
                }

                if ((string)$case['value'] === (string)$switchExpression) {
                    $selectedCase = $index;
                    break;
                }
            }

            // If no case matches, use default (last case)
            if ($selectedCase === null) {
                $selectedCase = count($cases) - 1;
            }

            return [
                'success' => true,
                'selectedCase' => $selectedCase,
                'output' => [
                    'switchValue' => $switchExpression,
                    'selectedCase' => $selectedCase,
                    'matchedValue' => $cases[$selectedCase]['value']
                ],
                'message' => sprintf(
                    "Switch evaluated: %s matched case %s",
                    $switchExpression,
                    $cases[$selectedCase]['value']
                )
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => [],
                'message' => "Switch evaluation failed: " . $e->getMessage()
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

        // Use Laravel's data_get helper to extract nested values
        $extractedValue = data_get($sourceJson, $jsonPath);

        return [
            'success' => true,
            'output' => [
                $outputKey => $extractedValue,
                'extractedValue' => $extractedValue
            ],
            'message' => "Extracted {$jsonPath} from {$sourceVariable}"
        ];
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
