To use this:

Add the trait to your models:

use App\Traits\HasWitchcraftTriggers;

class User extends Model
{
    use HasWitchcraftTriggers;
    // ...
}

// This will automatically trigger any diagrams watching User creation
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// This will trigger diagrams watching User updates with status field
$user->update(['status' => 'active']);

// Manual trigger with variable
WitchcraftTrigger::execute('diagram-123', [
    'status' => 'active',
    'custom' => 'value'
]);

// Example usage in a controller or service
WitchcraftTrigger::execute('diagram-id', ['status' => 'active']);
