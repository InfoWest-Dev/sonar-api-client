# Sonar API Client - Implementation Plan

## Overview

Step-by-step implementation plan for rebranding and refactoring the package to v1.0.0.

## Prerequisites

- [ ] Access to `infowest-dev/sonar-api-client` GitHub repo
- [ ] Packagist account for `infowest-dev` organization
- [ ] Local development environment with PHP 8.0+

---

## Phase 1: Foundation & Namespace Rebrand

### 1.1 Update composer.json

- [ ] Change package name: `kengineering/sonar-api-client` → `infowest-dev/sonar-api-client`
- [ ] Update description
- [ ] Update PSR-4 autoload: `Kengineering\\Sonar\\` → `InfoWestDev\\Sonar\\`
- [ ] Remove `vlucas/phpdotenv` from require
- [ ] Add dev dependencies: `phpunit/phpunit`, `laravel/pint` (or `friendsofphp/php-cs-fixer`)
- [ ] Update author information
- [ ] Set minimum-stability if needed

**File:** `composer.json`

### 1.2 Rename Namespace in All PHP Files

Update namespace declarations and use statements in all files:

**Directories to update:**
- [ ] `src/Sonar/` - All source files
- [ ] `tests/` - All test files

**Search/Replace:**
- `Kengineering\Sonar` → `InfoWestDev\Sonar`
- `Kengineering\\Sonar` → `InfoWestDev\\Sonar` (escaped in strings)

### 1.3 Verify PSR-4 Autoloading

- [ ] Run `composer dump-autoload`
- [ ] Run `composer validate`
- [ ] Fix any autoloading errors

---

## Phase 2: Credential Handling Refactor

### 2.1 Create SonarConfig Class

- [ ] Create `src/Sonar/SonarConfig.php`
- [ ] Implement static credential storage
- [ ] Implement `setCredentials(string $url, string $token)`
- [ ] Implement `getUrl()` with env fallback
- [ ] Implement `getToken()` with env fallback
- [ ] Implement `reset()` for testing
- [ ] Add clear error messages

**File:** `src/Sonar/SonarConfig.php`

### 2.2 Update Request Class

- [ ] Remove `$_ENV` references
- [ ] Import and use `SonarConfig::getUrl()`
- [ ] Import and use `SonarConfig::getToken()`
- [ ] Remove credential validation check (SonarConfig handles this)

**File:** `src/Sonar/Graphql/Request.php`

### 2.3 Update Tests for New Credential Pattern

- [ ] Update test setup to use `SonarConfig::setCredentials()`
- [ ] Add `SonarConfig::reset()` in tearDown
- [ ] Add tests for SonarConfig class itself

---

## Phase 3: Bug Fixes

### 3.1 Fix Pluralization Bug

- [ ] Add `const OBJECT_MULTIPLE_NAME = 'inventory_model_field_data'` to `InventoryModelFieldData`
- [ ] Audit other SonarObject classes for similar issues
- [ ] Add tests to verify correct GraphQL field names

**File:** `src/Sonar/Objects/InventoryModelFieldData.php`

### 3.2 Remove Unused Code

- [ ] Remove unused `Illuminate\Support\Str` import from `SonarObject.php` (if present)
- [ ] Remove any other dead code discovered during refactor

---

## Phase 4: Code Quality

### 4.1 PSR-12 Code Style

- [ ] Install code style fixer: `composer require --dev laravel/pint`
- [ ] Create `pint.json` configuration (PSR-12 preset)
- [ ] Run fixer: `./vendor/bin/pint`
- [ ] Review and commit changes

### 4.2 Run Test Suite

- [ ] Run `./vendor/bin/phpunit`
- [ ] Fix any failing tests
- [ ] Ensure all tests pass with new namespace and credential pattern

---

## Phase 5: GitHub Actions CI/CD

### 5.1 Create Tests Workflow

- [ ] Create `.github/workflows/tests.yml`
- [ ] Configure PHP version matrix (8.0, 8.1, 8.2, 8.3)
- [ ] Run composer install and phpunit

**File:** `.github/workflows/tests.yml`

```yaml
name: Tests

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        php: ['8.0', '8.1', '8.2', '8.3']

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          coverage: none

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Run tests
        run: ./vendor/bin/phpunit
```

### 5.2 Create Code Style Workflow

- [ ] Create `.github/workflows/code-style.yml`
- [ ] Run pint in test mode

**File:** `.github/workflows/code-style.yml`

```yaml
name: Code Style

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]

jobs:
  style:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Check code style
        run: ./vendor/bin/pint --test
```

### 5.3 Create Security Workflow

- [ ] Create `.github/workflows/security.yml`
- [ ] Run composer audit
- [ ] Schedule weekly checks

**File:** `.github/workflows/security.yml`

```yaml
name: Security

on:
  push:
    branches: [main]
  pull_request:
    branches: [main]
  schedule:
    - cron: '0 0 * * 0'  # Weekly on Sunday

jobs:
  security:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Security audit
        run: composer audit
```

### 5.4 Create Release Workflow

- [ ] Create `.github/workflows/release.yml`
- [ ] Trigger on tag push
- [ ] Create GitHub release

**File:** `.github/workflows/release.yml`

```yaml
name: Release

on:
  push:
    tags:
      - 'v*'

jobs:
  release:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Create Release
        uses: softprops/action-gh-release@v1
        with:
          generate_release_notes: true
```

### 5.5 Configure Dependabot

- [ ] Create `.github/dependabot.yml`

**File:** `.github/dependabot.yml`

```yaml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
    open-pull-requests-limit: 5

  - package-ecosystem: "github-actions"
    directory: "/"
    schedule:
      interval: "weekly"
```

---

## Phase 6: Documentation

### 6.1 Update README

- [ ] Update package name and namespace references
- [ ] Add installation instructions
- [ ] Add configuration section (SonarConfig usage)
- [ ] Add basic usage examples
- [ ] Add badges (tests, code style, packagist version)

**File:** `README.md`

### 6.2 Add CHANGELOG

- [ ] Create `CHANGELOG.md`
- [ ] Document v1.0.0 changes

---

## Phase 7: Release

### 7.1 Packagist Setup

- [ ] Register package on Packagist: `infowest-dev/sonar-api-client`
- [ ] Configure GitHub webhook for auto-updates

### 7.2 Tag v1.0.0

- [ ] Final review of all changes
- [ ] Commit all changes
- [ ] Create and push tag: `git tag v1.0.0 && git push origin v1.0.0`
- [ ] Verify GitHub release is created
- [ ] Verify Packagist shows new version

### 7.3 Update Consuming Projects

- [ ] Update `selfservice` project's composer.json
- [ ] Replace `kengineering/sonar-api-client` with `infowest-dev/sonar-api-client`
- [ ] Update namespace imports throughout codebase
- [ ] Add `SonarConfig::setCredentials()` call in service provider
- [ ] Run `composer update`
- [ ] Test functionality

---

## Verification Checklist

- [ ] `composer validate` passes
- [ ] `composer install` succeeds
- [ ] All tests pass
- [ ] Code style check passes
- [ ] Security audit passes
- [ ] Package installable from Packagist
- [ ] Credentials work with explicit setter
- [ ] Credentials work with env fallback
- [ ] InventoryModelFieldData query works (pluralization fixed)
