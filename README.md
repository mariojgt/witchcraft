# Witchcraft: Laravel Workflow Automation Package

## Overview

Witchcraft is a powerful Laravel package that provides a visual workflow automation system, allowing developers and non-technical users to create complex workflows using a node-based interface.

## Features

- 🔧 Visual Workflow Editor
- 🚀 Multiple Node Types
- 🔗 Flexible Condition Handling
- 🌐 Model Event Triggers
- 🤖 Artisan Command Execution
- 📡 API Integration

## Installation

Install the package via Composer:

```bash
composer require mariojgt/witchcraft
```

### Publish Configuration (Optional)

```bash
php artisan vendor:publish --provider="Mariojgt\Witchcraft\WitchcraftServiceProvider"
```

## Supported Node Types

### 1. Model Select Node
- Trigger workflows based on model events
- Support for create, update, delete, and restore events
- Test mode for easy workflow development

### 2. Condition Node
- Multiple condition types: Equals, Not Equals, Contains
- Flexible variable sourcing
- Support for complex comparisons

### 3. Artisan Command Node
- Execute Laravel Artisan commands within workflows
- Capture and save command output
- Full command line preview

### 4. API Request Node
- Make HTTP requests as part of your workflow
- Configurable methods, headers, and body
- Option to save response data

### 5. Notification Node
- Create custom notifications
- Variable interpolation
- Multiple notification types

### 6. Variable Node
- Set and manipulate variables
- Use in complex workflow logic

### 7. JSON Extract Node
- Extract specific values from JSON data
- Support for nested property access

## Basic Usage

### Creating a Workflow

1. Navigate to the Workflow Editor
2. Drag and drop nodes
3. Connect nodes to create your workflow logic
4. Save and execute

### Example Workflow

```
[Model Select] -> [Condition] -> [Notification/Artisan Command]
```

## Advanced Features

### Model Event Triggers

Add the `HasWitchcraftTriggers` trait to your models:

```php
use Mariojgt\Witchcraft\Traits\HasWitchcraftTriggers;

class User extends Model
{
    use HasWitchcraftTriggers;

    // Your model code
}
```

### Programmatic Workflow Execution

```php
use Mariojgt\Witchcraft\Services\WitchcraftTrigger;

// Execute a workflow
WitchcraftTrigger::execute(8, ['status' => 'active']);
```

## Configuration

Publish the configuration file to customize package behavior:

```bash
php artisan vendor:publish --tag="witchcraft-config"
```

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a Pull Request

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

## Credits

- [Mario Tarosso](https://github.com/mariojgt)
- [All Contributors](../../contributors)

## Support

If you discover any security-related issues, please email mario.junior.dev@gmail.com instead of using the issue tracker.
