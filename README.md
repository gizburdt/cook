# Cook

[![Latest Version on Packagist](https://img.shields.io/packagist/v/gizburdt/cook.svg?style=flat-square)](https://packagist.org/packages/gizburdt/cook)
[![Total Downloads](https://img.shields.io/packagist/dt/gizburdt/cook.svg?style=flat-square)](https://packagist.org/packages/gizburdt/cook)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)

Opinionated package to speed up new Laravel projects.

## Installation

You can install the package via composer:

``` bash
composer require gizburdt/cook --dev
```

## Usage

``` php
php artisan cook:install
```

## Todo
- Use `composer config http-basic.nova.laravel.com your-nova-account-email@your-domain.com your-license-key` for the auth file
- Use `composer config repositories.nova '{"type": "composer", "url": "https://nova.laravel.com"}' --file composer.json` for the Nova repository

## Testing

``` bash
composer test
```
