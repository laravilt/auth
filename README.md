# Auth Plugin for Laravilt

Auth plugin for Laravilt

## Installation

You can install the plugin via composer:

```bash
composer require laravilt/auth
```

## Usage

Register the plugin in your Panel provider:

```php
use Laravilt\Auth\AuthPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugins([
            AuthPlugin::make(),
        ]);
}
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag="laravilt-auth-config"
```

## Assets

Publish the plugin assets:

```bash
php artisan vendor:publish --tag="laravilt-auth-assets"
```

## Testing

```bash
composer test
```

## Code Style

```bash
composer format
```

## Static Analysis

```bash
composer analyse
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
