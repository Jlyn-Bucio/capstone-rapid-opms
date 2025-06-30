# Rapid OPMs (Online Project Management System)

Rapid OPMs is a comprehensive, web-based suite designed to streamline operations and project management for construction and building companies. It provides tools for managing users, projects, billing, inventory, suppliers, and customers, all from a centralized dashboard.

## Semantic Versioning

This project follows [Semantic Versioning](https://semver.org/) (SemVer). The version format is `MAJOR.MINOR.PATCH`.

| Change Type               | Rule                                           | Example          |
| ------------------------- | ---------------------------------------------- | ---------------- |
| **Bug Fixes**             | Increment `PATCH` version                      | `1.0.0` -> `1.0.1` |
| **New Features** (non-breaking) | Increment `MINOR` version, reset `PATCH` to 0 | `1.0.1` -> `1.1.0` |
| **Breaking Changes**      | Increment `MAJOR` version, reset `MINOR` and `PATCH` to 0 | `1.1.0` -> `2.0.0` |

### How to Update the Version

The application version is defined as a constant in `includes/version.php`. To update it, simply change the `APP_VERSION` value:

```php
// includes/version.php
define('APP_VERSION', '1.0.1'); // Update this value
```
