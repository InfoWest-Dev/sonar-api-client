# Sonar API Client - Design Document

## Overview

This document captures the architectural decisions for rebranding and refactoring the Sonar API Client package from `kengineering/sonar-api-client` to `infowest-dev/sonar-api-client`.

## Version

**Target Version:** v1.0.0 (fresh start)

## Package Identity

| Aspect | Old | New |
|--------|-----|-----|
| Package name | `kengineering/sonar-api-client` | `infowest-dev/sonar-api-client` |
| Namespace | `Kengineering\Sonar` | `InfoWestDev\Sonar` |
| GitHub repo | `kengineering/sonar-api-client` | `infowest-dev/sonar-api-client` |

## Credential Handling

### Problem

The original package used `$_ENV['SONAR_URL']` and `$_ENV['SONAR_KEY']` directly, which:
- Doesn't work in Laravel (which doesn't populate `$_ENV` by default)
- Included `vlucas/phpdotenv` as a dependency but never initialized it
- Had no way to explicitly pass credentials

### Solution: Hybrid Static Setter + Environment Fallback

A dedicated `SonarConfig` class with:

1. **Explicit static setter** (priority) - consumers call `SonarConfig::setCredentials()`
2. **Environment fallback** - falls back to `getenv()` if no explicit credentials
3. **Clear error messages** - tells consumers exactly what to do if neither is set

```php
namespace InfoWestDev\Sonar;

class SonarConfig
{
    private static ?string $url = null;
    private static ?string $token = null;

    public static function setCredentials(string $url, string $token): void
    {
        self::$url = $url;
        self::$token = $token;
    }

    public static function getUrl(): string
    {
        $url = self::$url ?? getenv('SONAR_URL') ?: getenv('INFOWEST_SONAR_URL') ?: null;

        if ($url === null) {
            throw new \RuntimeException(
                'Sonar URL not configured. Call SonarConfig::setCredentials() or set SONAR_URL env var.'
            );
        }

        return $url;
    }

    public static function getToken(): string
    {
        $token = self::$token ?? getenv('SONAR_TOKEN') ?: getenv('INFOWEST_SONAR_TOKEN') ?: null;

        if ($token === null) {
            throw new \RuntimeException(
                'Sonar token not configured. Call SonarConfig::setCredentials() or set SONAR_TOKEN env var.'
            );
        }

        return $token;
    }

    public static function reset(): void
    {
        self::$url = null;
        self::$token = null;
    }
}
```

### Usage in Laravel

```php
// In AppServiceProvider::boot() or a dedicated SonarServiceProvider
SonarConfig::setCredentials(
    config('services.sonar.host'),
    config('services.sonar.token')
);
```

### Usage in Raw PHP

```php
// Option A: Explicit
SonarConfig::setCredentials($url, $token);

// Option B: Set environment variables
// SONAR_URL and SONAR_TOKEN will be auto-detected
```

## Pluralization Fix

### Problem

The package auto-pluralizes GraphQL field names by appending "s":
```php
$access_name = $root_object::OBJECT_MULTIPLE_NAME ?? "{$object_name}s";
```

This fails for `InventoryModelFieldData` because:
- Auto-pluralized: `inventory_model_field_datas` (wrong)
- Sonar API expects: `inventory_model_field_data` (correct - already plural)

### Solution

Add `OBJECT_MULTIPLE_NAME` constant to classes where auto-pluralization fails:

```php
class InventoryModelFieldData extends SonarObject
{
    const OBJECT_MULTIPLE_NAME = 'inventory_model_field_data';
    // ... rest of class
}
```

This pattern can be applied to any other objects with irregular pluralization.

## Dependency Changes

### Removed

- `vlucas/phpdotenv` - Not needed; consuming applications handle env loading

### Retained

- `guzzlehttp/guzzle` - HTTP client for API requests
- `php: ^8.0` - Minimum PHP version

### Added (Dev Dependencies)

- `phpunit/phpunit` - Testing framework
- `friendsofphp/php-cs-fixer` or `laravel/pint` - Code style fixer

## PSR Compliance

The package should comply with:
- **PSR-4** - Autoloading (namespace matches directory structure)
- **PSR-12** - Code style (run formatter across codebase)

## GitHub Actions CI/CD

### Workflows to Implement

#### 1. Tests (`tests.yml`)
- **Trigger:** Push to main, pull requests
- **Matrix:** PHP 8.0, 8.1, 8.2, 8.3
- **Steps:**
  - Checkout code
  - Setup PHP with extensions
  - Install dependencies (composer install)
  - Run PHPUnit tests

#### 2. Code Style (`code-style.yml`)
- **Trigger:** Push to main, pull requests
- **Steps:**
  - Run PHP-CS-Fixer or Pint in dry-run mode
  - Fail if code doesn't meet PSR-12 standards

#### 3. Security (`security.yml`)
- **Trigger:** Push to main, pull requests, scheduled (weekly)
- **Steps:**
  - Run `composer audit` for dependency vulnerabilities
  - Optionally integrate with Dependabot for automated PRs

#### 4. Release & Packagist (`release.yml`)
- **Trigger:** Tag push (v*)
- **Steps:**
  - Create GitHub release from tag
  - Packagist auto-updates via webhook (configure in Packagist settings)

### Dependabot Configuration

```yaml
# .github/dependabot.yml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
    open-pull-requests-limit: 5
```

### Branch Protection (Recommended)

Configure on GitHub:
- Require status checks to pass (tests, code-style)
- Require PR reviews before merging to main

## Deferred Decisions

### Guzzle Client Injection

Currently `Query` accepts an optional `GuzzleHttp\Client` for testing/proxies. We're keeping this pattern but may revisit:

- Should credentials be passable per-query?
- Should `SonarConfig` hold a configured client instance?

Decision deferred until we have clearer use cases.
