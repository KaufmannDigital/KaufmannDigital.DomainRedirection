# KaufmannDigital.DomainRedirection

A flexible Neos CMS package for configuring domain and path-based HTTP redirects using plain strings or regex patterns.

## Features

- **Domain-based redirects**: Redirect entire domains to new locations
- **Regex support**: Use regex patterns for both domain matching and path transformations
- **Path transformation rules**: Define multiple rules per domain with regex patterns
- **Custom status codes**: Configure individual status codes (301, 302, 307, etc.) per redirect
- **Capture group support**: Use regex capture groups (`$1`, `$2`, etc.) in replacements
- **Flexible configuration**: Works with plain domain strings or complex regex patterns
- **HTTP Middleware**: Runs after Neos redirect middleware for optimal performance

## Installation

Install via Composer:

```bash
composer require kaufmanndigital/domainredirection
```

## Configuration

Add your redirect configuration to your `Settings.yaml`:

```yaml
KaufmannDigital:
  DomainRedirection:
    redirects:
      - domainPattern: 'old-domain.com'
        target: 'https://new-domain.com'
        statusCode: 301
```

## Usage Examples

### 1. Simple Domain Redirect

Redirect an entire domain to a new location:

```yaml
KaufmannDigital:
  DomainRedirection:
    redirects:
      - domainPattern: 'old-domain.com'
        target: 'https://new-domain.com'
        statusCode: 301
```

**Result:**
- `old-domain.com` → `https://new-domain.com`
- `old-domain.com/any/path` → `https://new-domain.com`

### 2. Domain Redirect with Path Preservation

Use regex to preserve and transform the path:

```yaml
KaufmannDigital:
  DomainRedirection:
    redirects:
      - domainPattern: 'old-domain.com/(.*)$'
        target: 'https://new-domain.com/$1'
        statusCode: 301
```

**Result:**
- `old-domain.com/about` → `https://new-domain.com/about`
- `old-domain.com/contact/form` → `https://new-domain.com/contact/form`

### 3. Subdomain to Path Redirect

Redirect a subdomain to a specific path on the main domain:

```yaml
KaufmannDigital:
  DomainRedirection:
    redirects:
      - domainPattern: 'blog.example.com'
        target: 'https://example.com/blog'
        statusCode: 307
```

**Result:**
- `blog.example.com` → `https://example.com/blog`
- `blog.example.com/article` → `https://example.com/blog`

### 4. Subdomain with Path Transformation

Redirect a subdomain and append the path to a specific location:

```yaml
KaufmannDigital:
  DomainRedirection:
    redirects:
      - domainPattern: 'karriere.example.com/(.*)$'
        target: 'https://example.com/careers#$1'
        statusCode: 307
```

**Result:**
- `karriere.example.com/jobs` → `https://example.com/careers#jobs`
- `karriere.example.com/apply/form` → `https://example.com/careers#apply/form`

### 5. Path-Specific Rules

Define multiple rules for different path patterns on the same domain:

```yaml
KaufmannDigital:
  DomainRedirection:
    redirects:
      - domainPattern: 'example.com'
        target: 'https://new-example.com'
        statusCode: 301
        rules:
          - pattern: '^/old-section/(.*)$'
            replacement: '/new-section/$1'
            statusCode: 301
          - pattern: '^/legacy/(.*)$'
            replacement: '/modern/$1'
            statusCode: 302
```

**Result:**
- `example.com/old-section/page` → `https://new-example.com/new-section/page`
- `example.com/legacy/content` → `https://new-example.com/modern/content` (302)
- `example.com/other` → `https://new-example.com` (fallback)

### 6. Multiple Domains with Different Rules

Configure multiple domain redirects in the same configuration:

```yaml
KaufmannDigital:
  DomainRedirection:
    redirects:
      - domainPattern: 'old-site.com'
        target: 'https://new-site.com'
        statusCode: 301

      - domainPattern: 'beta.example.com'
        target: 'https://example.com/beta'
        statusCode: 307
        rules:
          - pattern: '^/test/(.*)$'
            replacement: '/testing/$1'
            statusCode: 302
```

## Configuration Options

### Main Configuration

- **`domainPattern`** (required): Domain to match. Can be:
  - Plain string: `'example.com'`
  - Regex pattern: `'example.com/(.*)$'`

- **`target`** (required): Target URL to redirect to

- **`statusCode`** (optional, default: 301): HTTP status code for the redirect
  - `301` - Permanent redirect
  - `302` - Temporary redirect
  - `307` - Temporary redirect (preserves HTTP method)
  - `308` - Permanent redirect (preserves HTTP method)

- **`pattern`** (optional): Regex pattern for path transformation (alternative to using regex in `domainPattern`)

- **`rules`** (optional): Array of path-specific rules

### Rules Configuration

- **`pattern`** (required): Regex pattern to match against the path
- **`replacement`** (required): Replacement string (can include capture groups like `$1`, `$2`)
- **`statusCode`** (optional): Override status code for this specific rule

## How It Works

1. The middleware checks each configured redirect in order
2. If `domainPattern` matches (either as plain string or regex):
   - First, it checks if any `rules` match the current path
   - If a rule matches, it applies the rule's transformation
   - If no rule matches, it uses the default `target`
3. The middleware runs **after** the Neos redirect middleware
4. If no redirect matches, the request continues to the next middleware

## License

This package is licensed under the MIT License.

## Author

KaufmannDigital GmbH - https://www.kaufmann.digital