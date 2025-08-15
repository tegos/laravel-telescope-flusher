<h1 align="center">Laravel Telescope Flusher</h1>

<p align="center">
Flush all Laravel Telescope data safely in local environments.
</p>

<p align="center">
  <a href="https://packagist.org/packages/tegos/laravel-telescope-flusher">
    <img src="https://img.shields.io/packagist/v/tegos/laravel-telescope-flusher.svg" alt="Latest Version on Packagist">
  </a>
  <a href="https://packagist.org/packages/tegos/laravel-telescope-flusher">
    <img src="https://img.shields.io/packagist/dt/tegos/laravel-telescope-flusher.svg" alt="Total Downloads">
  </a>
  <a href="https://www.php.net/">
    <img src="https://img.shields.io/badge/PHP-8.1%2B-blue" alt="PHP Version">
  </a>
  <a href="https://laravel.com/">
    <img src="https://img.shields.io/badge/Laravel-10%2B-brightgreen" alt="Laravel Version">
  </a>
  <a href="LICENSE.md">
    <img src="https://img.shields.io/badge/license-MIT-brightgreen.svg" alt="Software License">
  </a>
</p>

------

**Laravel Telescope Flusher** is a simple package that provides laravel artisan command to completely flush all telescope
data from your database. It ensures a clean slate for debugging and monitoring while preventing execution in production
environments.

[Efficiently Managing Telescope Entries with Laravel-Telescope-Flusher](https://dev.to/tegos/efficiently-managing-telescope-entries-with-laravel-telescope-flusher-484a)

## Installation

You can install the package via Composer:

```bash
composer require tegos/laravel-telescope-flusher --dev
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
