<?php
namespace Mariojgt\Witchcraft\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeNodeCommand extends Command
{
    protected $signature = 'witchcraft:make-node {name} {--category=Custom}';
    protected $description = 'Create a new Witchcraft node';

    public function handle()
    {
        $name = $this->argument('name');
        $category = $this->option('category');

        // Create base directories if they don't exist
        if (!is_dir(app_path('Witchcraft/Handlers'))) {
            mkdir(app_path('Witchcraft/Handlers'), 0755, true);
        }
        if (!is_dir(resource_path('js/witchcraft/nodes'))) {
            mkdir(resource_path('js/witchcraft/nodes'), 0755, true);
        }

        // Create the handler
        $this->createNodeHandler($name);

        // Create the Vue component
        $this->createVueComponent($name, $category);

        $this->info("Node {$name} created successfully in:");
        $this->info("- Handler: app/Witchcraft/Handlers/{$name}NodeHandler.php");
        $this->info("- Component: resources/js/witchcraft/nodes/{$name}Node.vue");
    }

    protected function createNodeHandler($name)
    {
        $stub = file_get_contents(__DIR__ . '/stubs/NodeHandler.stub');
        $className = Str::studly($name) . 'NodeHandler';
        $componentName = Str::studly($name) . 'Node';

        $content = str_replace(
            [
                '{{className}}',
                '{{nodeType}}',
                '{{componentName}}'
            ],
            [
                $className,
                Str::kebab($name),
                $componentName
            ],
            $stub
        );

        $path = app_path("Witchcraft/Handlers/{$className}.php");
        file_put_contents($path, $content);
    }

    protected function createVueComponent($name, $category)
    {
        $stub = file_get_contents(__DIR__ . '/stubs/Node.stub');
        $componentName = Str::studly($name) . 'Node';

        $content = str_replace(
            [
                '{{componentName}}',
                '{{category}}',
                '{{nodeType}}'
            ],
            [
                $componentName,
                $category,
                Str::kebab($name)
            ],
            $stub
        );

        $path = resource_path("js/witchcraft/nodes/{$componentName}.vue");
        file_put_contents($path, $content);
    }
}
