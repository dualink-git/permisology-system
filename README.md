# A comprehensive Filament plugin for managing role-based permissions and access control. It includes features like blacklists and public access lists for endpoints, and the ability to restrict access to users or roles based on IP and DNS

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dualink/permisology-system.svg?style=flat-square)](https://packagist.org/packages/dualink/permisology-system)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/dualink/permisology-system/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/dualink/permisology-system/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/dualink/permisology-system/fix-php-code-styling.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/dualink/permisology-system/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/dualink/permisology-system.svg?style=flat-square)](https://packagist.org/packages/dualink/permisology-system)



This is where your description should go. Limit it to a paragraph or two. Consider adding a small example.

## Installation

You can install the package via composer:

```bash
composer require dualink/permisology-system
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="permisology-system-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="permisology-system-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="permisology-system-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

```php
$permisologySystem = new PermisologySystem\PermisologySystem();
echo $permisologySystem->echoPhrase('Hello, PermisologySystem!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Joaquín D. Giuliano](https://github.com/DuaLinK)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
