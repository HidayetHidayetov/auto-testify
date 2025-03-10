# AutoTestify

**AutoTestify** is a powerful Laravel package designed to automatically generate comprehensive test files for your Eloquent models. It simplifies the testing process by creating CRUD (Create, Read, Update, Delete) and uniqueness tests, saving you time and ensuring your models are thoroughly tested.

---

## Features

- **Automatic CRUD Tests**: Generates tests for creating, retrieving, updating, and deleting model instances.
- **Uniqueness Testing**: Detects unique fields from the database schema or model configuration and generates corresponding tests.
- **Database Flexibility**: Works with or without a database connection:
    - If migrated and `doctrine/dbal` is installed, detects unique fields from the database.
    - Otherwise, uses the `$unique` property defined in your model.
- **Resilient Design**: Skips database-dependent tests gracefully if no connection is available.
- **Customizable**: Easily extendable for additional test cases or model-specific logic.

---

## Requirements

- **PHP**: 8.1 or higher
- **Laravel**: 11.0 or higher
- **Optional**: `doctrine/dbal` (for database schema detection)

---

## Installation

### Step 1: Install via Composer
Add the package to your Laravel project:

```bash
composer require hidayetov/auto-testify