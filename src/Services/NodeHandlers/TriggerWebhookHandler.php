<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

use Illuminate\Support\Facades\Http;

class TriggerWebhookHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        try {
            $webhookUrl = $this->getData($node, 'webhookUrl');
            $method = $this->getData($node, 'method', 'POST');
            $payload = $this->getData($node, 'payload', '{}');
            $headers = $this->getData($node, 'headers', '{}');
            $waitForResponse = $this->getData($node, 'waitForResponse', false);
            $responseVariable = $this->getData($node, 'responseVariable', 'webhookResponse');

            if (empty($webhookUrl)) {
                return $this->error('Webhook URL is required');
            }

            // Replace variables in payload
            $processedPayload = $this->replaceVariables($payload, $variables);

            // Parse headers
            $headersArray = [];
            if (!empty($headers)) {
                $headersArray = json_decode($headers, true) ?: [];
            }

            // Parse payload for POST/PUT/PATCH requests
            $payloadData = [];
            if (in_array(strtoupper($method), ['POST', 'PUT', 'PATCH']) && !empty($processedPayload)) {
                $payloadData = json_decode($processedPayload, true) ?: [];
            }

            // Make HTTP request
            $httpClient = Http::withHeaders($headersArray);

            if (!$waitForResponse) {
                // Fire and forget
                $httpClient->timeout(5);
            }

            $response = match (strtoupper($method)) {
                'GET' => $httpClient->get($webhookUrl),
                'POST' => $httpClient->post($webhookUrl, $payloadData),
                'PUT' => $httpClient->put($webhookUrl, $payloadData),
                'PATCH' => $httpClient->patch($webhookUrl, $payloadData),
                default => $httpClient->post($webhookUrl, $payloadData)
            };

            $output = [
                'webhookStatus' => $response->status(),
                'webhookSuccess' => $response->successful()
            ];

            if ($waitForResponse) {
                $output[$responseVariable] = $response->json() ?: $response->body();
            }

            return $this->success(
                $output,
                "Webhook triggered successfully to {$webhookUrl} ({$response->status()})"
            );
        } catch (\Exception $e) {
            return $this->error("Webhook trigger failed: " . $e->getMessage());
        }
    }
}
