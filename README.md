# Laravel Telescope Flusher

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tegos/laravel-telescope-flusher.svg?style=flat-square)](https://packagist.org/packages/tegos/laravel-telescope-flusher)
[![Total Downloads](https://img.shields.io/packagist/dt/tegos/laravel-telescope-flusher.svg?style=flat-square)](https://packagist.org/packages/tegos/laravel-telescope-flusher)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-9%2B-brightgreen)](https://laravel.com/)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE.md)

![Laravel Telescope Flusher](assets/poster.jpg)

**Laravel Telescope Flusher** is a simple package that provides an Artisan command to completely flush all Telescope
data from your database. It ensures a clean slate for debugging and monitoring while preventing execution in production
environments.

## Installation

You can install the package via Composer:

```bash
composer require tegos/laravel-telescope-flusher
```

## Usage

Once installed, you can run the following command to flush Telescope data:

```bash
php artisan telescope:flush
```

### Behavior

- ✅ Only runs in **local** environments (prevents accidental execution in production).
- ✅ Checks if **Telescope is installed** before running.
- ✅ Truncates all Telescope-related tables.
- ✅ Optimizes the `telescope_entries` table (MySQL).

### Testing

You can run tests using:

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for contribution guidelines.

## Security

If you discover any security-related issues, please email **tegosiv@gmail.com** instead of using the issue tracker.

## Credits

- **[Ivan Mykhavko](https://github.com/tegos)**
- **[All Contributors](../../contributors)**

## License

This package is open-source software licensed under the **MIT License**. See [LICENSE](LICENSE.md) for details.

---

<p align="center">
  <a href="https://savelife.in.ua/en/donate-en/" target="_blank">
    <img src="./assets/come-back-alive.svg" alt="Donate"/>
  </a>
</p> 
