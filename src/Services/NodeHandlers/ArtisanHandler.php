<?php

namespace Mariojgt\Witchcraft\Services\NodeHandlers;

class ArtisanHandler extends BaseNodeHandler
{
    public function handle(array $node, array $variables): array
    {
        try {
            $command = $this->getData($node, 'command');
            $rawArguments = $this->getData($node, 'arguments', '');
            $saveOutput = $this->getData($node, 'saveOutput', true);
            $outputKey = $this->getData($node, 'outputKey', 'artisanOutput');

            if (!$command) {
                return $this->error('Artisan command is required');
            }

            // Parse arguments
            $arguments = [];
            if (!empty(trim($rawArguments))) {
                $argumentArray = array_filter(array_map('trim', explode("\n", $rawArguments)));
                foreach ($argumentArray as $arg) {
                    if (strpos($arg, '=') !== false) {
                        [$key, $value] = explode('=', $arg, 2);
                        $arguments[trim($key)] = trim($value);
                    } else {
                        $arguments[] = trim($arg);
                    }
                }
            }

            // Prepare output buffer
            $output = new \Symfony\Component\Console\Output\BufferedOutput();

            // Run Artisan command
            $exitCode = \Artisan::call($command, $arguments, $output);
            $commandOutput = $output->fetch();

            return $this->success(
                $saveOutput ? [$outputKey => $commandOutput] : [],
                "Artisan command '{$command}' executed. Exit code: {$exitCode}"
            );
        } catch (\Exception $e) {
            return $this->error("Artisan command failed: " . $e->getMessage());
        }
    }
}
