# Sonar API Client - Backlog

## Overview

Items deferred from v1.0.0 for future consideration.

---

## Deferred from v1.0.0

### Guzzle Client Injection Pattern

**Context:** The `Query` class currently accepts an optional `GuzzleHttp\Client` in its constructor for testing and proxy scenarios.

**Questions to resolve:**
- Should credentials be passable per-query instead of/in addition to global config?
- Should `SonarConfig` hold a configured client instance?
- How should timeout and retry configuration work?

**Potential approaches:**
1. Keep current pattern (optional client in constructor)
2. Add client configuration to `SonarConfig`
3. Create a `SonarClient` class that wraps configuration + client

**Decision:** Revisit after v1.0.0 based on real-world usage patterns.

---

### Additional Pluralization Issues

**Context:** We fixed `InventoryModelFieldData`, but other Sonar objects may have irregular pluralization.

**Action:** Monitor for similar errors and add `OBJECT_MULTIPLE_NAME` constants as needed.

**Known patterns to watch:**
- Words ending in "data" (already plural)
- Words ending in "s" (may double-pluralize)
- Irregular English plurals

---

### Comprehensive Documentation

**Context:** v1.0.0 includes basic README updates. More comprehensive documentation may be valuable.

**Potential additions:**
- Full API reference
- More usage examples
- Troubleshooting guide
- Contributing guidelines
- Architecture overview

**Decision:** Gauge interest and add based on user feedback.

---

### Static Analysis

**Context:** Adding PHPStan or Psalm for static type checking.

**Benefits:**
- Catch type errors at build time
- Improve code quality
- Better IDE support

**Action:** Consider adding to CI in future version.

---

### Test Coverage Improvements

**Context:** Existing tests need namespace updates. Coverage level unknown.

**Potential improvements:**
- Add coverage reporting to CI
- Increase coverage for edge cases
- Add integration tests (against mock Sonar API)

---

## Future Feature Ideas

### Laravel Service Provider Package

**Idea:** Create a separate `infowest-dev/sonar-laravel` package that provides:
- Auto-registration of `SonarConfig` from Laravel config
- Facade for convenient access
- Artisan commands for common operations

**Benefit:** Zero-config setup for Laravel users.

---

### Retry and Circuit Breaker

**Idea:** Add resilience patterns for API calls:
- Automatic retry with exponential backoff
- Circuit breaker for failing endpoints
- Request rate limiting

---

### Caching Layer

**Idea:** Optional caching for read operations:
- Cache query results
- Configurable TTL
- Cache invalidation on mutations

---

### Webhook Support

**Idea:** If Sonar supports webhooks, add handling for:
- Account updates
- Inventory changes
- Billing events

---

## Technical Debt

### Remove Unused Imports

**Files to check:**
- `SonarObject.php` - potentially has unused `Illuminate\Support\Str` import

### Consistent Error Handling

**Current state:** Mix of exceptions and return values for error conditions.

**Improvement:** Standardize on exception-based error handling with custom exception classes.

### Type Declarations

**Current state:** Mixed use of type hints and docblocks.

**Improvement:** Add strict type declarations throughout for PHP 8.0+ features.

---

## Notes

Add items to this backlog as they're discovered during development or reported by users. Prioritize based on:
1. User impact
2. Effort required
3. Strategic value
