# Code Coverage Setup for Xavante Core

## Prerequisites

To enable code coverage in PHPUnit, you need one of the following PHP extensions:

### Option 1: Xdebug (Recommended for Development)

```bash
# Install Xdebug via PECL
pecl install xdebug

# Or via Homebrew (macOS)
brew install php@8.4-xdebug

# Or via apt (Ubuntu/Debian)
sudo apt-get install php-xdebug
```

Add to your `php.ini`:
```ini
zend_extension=xdebug
xdebug.mode=coverage
```

### Option 2: PCOV (Faster, Production-Friendly)

```bash
# Install PCOV via PECL
pecl install pcov
```

Add to your `php.ini`:
```ini
extension=pcov
pcov.enabled=1
```

### Option 3: Using Docker with Xdebug

Update your `docker/Dockerfile` to include Xdebug:

```dockerfile
# Add this to your Dockerfile
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Configure Xdebug for coverage
RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
```

## Running Tests with Coverage

Once you have a coverage driver installed:

### Generate HTML Coverage Report
```bash
vendor/bin/phpunit --coverage-html build/coverage/html
```

### Generate Clover XML (for CI/CD)
```bash
vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
```

### Display Coverage in Terminal
```bash
vendor/bin/phpunit --coverage-text
```

### Generate All Coverage Reports (using phpunit.xml config)
```bash
vendor/bin/phpunit --coverage-html build/coverage/html --coverage-clover build/coverage/clover.xml --coverage-text
```

### Run Specific Test with Coverage
```bash
vendor/bin/phpunit --coverage-text tests/Integration/Actions/MakeHttpRequestTest.php
```

## Coverage Configuration

The `phpunit.xml` has been configured with:

- **Source Code**: Includes `src/` directory
- **Exclusions**: Excludes `src/Models/Fixtures/` directory
- **Reports**:
  - HTML report in `build/coverage/html/`
  - Clover XML in `build/coverage/clover.xml`
  - Text report in `build/coverage/coverage.txt`

## Coverage Thresholds (Optional)

You can add coverage thresholds to fail tests if coverage is too low:

```xml
<coverage>
    <report>
        <html outputDirectory="build/coverage/html"/>
        <clover outputFile="build/coverage/clover.xml"/>
        <text outputFile="build/coverage/coverage.txt"/>
    </report>
    <threshold>
        <line>80</line>
        <method>80</method>
        <class>80</class>
    </threshold>
</coverage>
```

## CI/CD Integration

For continuous integration, you can use the Clover XML report:

```yaml
# Example GitHub Actions
- name: Run tests with coverage
  run: vendor/bin/phpunit --coverage-clover build/coverage/clover.xml

- name: Upload coverage to Codecov
  uses: codecov/codecov-action@v3
  with:
    file: build/coverage/clover.xml
```

## Docker Development Setup

If you're using Docker for development, update your `docker-compose.yaml`:

```yaml
services:
  app:
    build: ./docker
    environment:
      - XDEBUG_MODE=coverage
    volumes:
      - .:/var/www/html
```

Then run tests with coverage inside the container:

```bash
docker-compose exec app vendor/bin/phpunit --coverage-html build/coverage/html
```