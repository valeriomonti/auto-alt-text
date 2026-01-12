# Auto Alt Text - Test Suite

PHPUnit-based test suite for the Auto Alt Text WordPress plugin.

## Requirements

- PHP >= 7.4
- Composer
- WordPress (for integration tests)
- PHPUnit 9.6+ (installed via Composer)

## Installation

```bash
composer install
```

## Running Tests

### Run all tests

```bash
composer test
# or
./vendor/bin/phpunit
```

### Run with detailed output (testdox)

```bash
./vendor/bin/phpunit --testdox
```

### Run specific test suite

```bash
# Unit tests only
./vendor/bin/phpunit --testsuite Unit

# Integration tests only
./vendor/bin/phpunit --testsuite Integration
```

### Run specific test file

```bash
./vendor/bin/phpunit tests/Unit/Infrastructure/Http/WordPressHttpClientTest.php
```

### Generate code coverage report

```bash
composer test:coverage
# Report will be in coverage/index.html
```

## Test Structure

```
tests/
├── bootstrap.php              # Test bootstrap (loads WordPress + autoloader)
├── Unit/                      # Unit tests (isolated, no external dependencies)
│   └── Infrastructure/
│       └── Http/
│           └── WordPressHttpClientTest.php
└── Integration/               # Integration tests (require WordPress)
```

## Writing Tests

### Unit Test Example

```php
<?php

namespace AATXT\Tests\Unit\Infrastructure\Http;

use PHPUnit\Framework\TestCase;

class MyClassTest extends TestCase
{
    /**
     * @test
     */
    public function it_does_something(): void
    {
        // Arrange
        $instance = new MyClass();

        // Act
        $result = $instance->doSomething();

        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### Integration Test Example

For tests requiring WordPress functions, use the same structure but they'll run in the Integration suite.

## WordPress Integration

The test bootstrap automatically loads WordPress if available:

1. From local installation (4 levels up from plugin directory)
2. From WP_TESTS_DIR environment variable (WordPress test suite)
3. Tests are skipped if WordPress is not available

## Continuous Integration

Tests can be run in CI environments by:

1. Installing WordPress test suite
2. Setting `WP_TESTS_DIR` environment variable
3. Running `composer test`

Example for GitHub Actions:

```yaml
- name: Setup WordPress Test Suite
  run: |
    bash bin/install-wp-tests.sh wordpress_test root '' localhost latest

- name: Run Tests
  run: composer test
```

## Current Test Coverage

- **WordPressHttpClient**: 9 tests, 27 assertions
  - POST requests with JSON body
  - GET requests
  - Error handling (404, invalid URLs, network errors)
  - Custom headers
  - JSON response parsing

## Notes

- Tests use real HTTP requests to public APIs (JSONPlaceholder, httpbin.org)
- Network errors may cause test failures if APIs are unreachable
- PHP-DI deprecation warnings are expected with PHP 8.1+ and can be ignored
