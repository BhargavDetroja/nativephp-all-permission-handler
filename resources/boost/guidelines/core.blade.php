## bhargavdetroja/nativephp-all-permission-handle

A NativePHP Mobile plugin for all type of permission mobile permitions

### Installation

```bash
composer require bhargavdetroja/nativephp-all-permission-handle
```

### PHP Usage (Livewire/Blade)

Use the `AllPermissionHandler` facade:

@verbatim
<code-snippet name="Using AllPermissionHandler Facade" lang="php">
use Nativephp\AllPermissionHandler\Facades\AllPermissionHandler;

// Execute the plugin functionality
$result = AllPermissionHandler::execute(['option1' => 'value']);

// Get the current status
$status = AllPermissionHandler::getStatus();
</code-snippet>
@endverbatim

### Available Methods

- `AllPermissionHandler::execute()`: Execute the plugin functionality
- `AllPermissionHandler::getStatus()`: Get the current status

### Events

- `AllPermissionHandlerCompleted`: Listen with `#[OnNative(AllPermissionHandlerCompleted::class)]`

@verbatim
<code-snippet name="Listening for AllPermissionHandler Events" lang="php">
use Native\Mobile\Attributes\OnNative;
use Nativephp\AllPermissionHandler\Events\AllPermissionHandlerCompleted;

#[OnNative(AllPermissionHandlerCompleted::class)]
public function handleAllPermissionHandlerCompleted($result, $id = null)
{
    // Handle the event
}
</code-snippet>
@endverbatim

### JavaScript Usage (Vue/React/Inertia)

@verbatim
<code-snippet name="Using AllPermissionHandler in JavaScript" lang="javascript">
import { allPermissionHandler } from '@nativephp/all-permission-handler';

// Execute the plugin functionality
const result = await allPermissionHandler.execute({ option1: 'value' });

// Get the current status
const status = await allPermissionHandler.getStatus();
</code-snippet>
@endverbatim