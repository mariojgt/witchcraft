<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class ApiHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        try {
            $requestType = $this->getData($node, 'requestType', 'external');
            $method = $this->getData($node, 'method', 'GET');
            $url = $this->getData($node, 'url');
            $saveResponse = $this->getData($node, 'saveResponse', true);
            $authenticatedRequest = $this->getData($node, 'authenticatedRequest', false);

            if (!$url) {
                return $this->error('URL is required for API requests');
            }

            // Parse parameters and body
            $params = json_decode($this->getData($node, 'params', '{}'), true);
            $body = json_decode($this->getData($node, 'body', '{}'), true);
            $headers = json_decode($this->getData($node, 'headers', '{}'), true);

            // Replace variables in URL, params, and body
            $url = $this->replaceVariables($url, $variables);
            $params = $this->replaceVariablesInArray($params, $variables);
            $body = $this->replaceVariablesInArray($body, $variables);

            // Prepare request options
            $requestOptions = [
                'headers' => $headers,
                'verify' => false
            ];

            // Add body for methods that support it
            if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
                $requestOptions['json'] = $body;
            }

            // Handle authenticated requests
            if ($authenticatedRequest && auth()->check()) {
                $headers['Authorization'] = 'Bearer ' . auth()->user()->createToken('api')->plainTextToken;
                $requestOptions['headers'] = $headers;
            }

            $client = new \GuzzleHttp\Client();

            // Add query parameters for GET requests
            if (strtoupper($method) === 'GET' && !empty($params)) {
                $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
            }

            $response = $client->request($method, $url, $requestOptions);
            $result = json_decode($response->getBody(), true);

            return $this->success(
                $saveResponse ? ['apiResponse' => $result] : [],
                "API {$requestType} request successful"
            );
        } catch (\Exception $e) {
            return $this->error("API request failed: " . $e->getMessage());
        }
    }

    protected function replaceVariablesInArray($array, $variables)
    {
        if (!is_array($array)) {
            return $array;
        }

        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $array[$key] = $this->replaceVariables($value, $variables);
            } elseif (is_array($value)) {
                $array[$key] = $this->replaceVariablesInArray($value, $variables);
            }
        }
        return $array;
    }
}
