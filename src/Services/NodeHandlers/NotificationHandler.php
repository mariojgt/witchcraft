<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class NotificationHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        $message = $this->getData($node, 'message', 'Default notification');
        $processedMessage = $this->replaceVariables($message, $variables);

        // Here you would integrate with your actual notification system
        // For now, just log it
        \Log::info("Notification: {$processedMessage}");

        return $this->success([
            'message' => $processedMessage,
            'extractedValue' => $processedMessage, // Assuming you want to return the processed message as an extracted value
        ], "Notification sent: {$processedMessage}");
    }
}
