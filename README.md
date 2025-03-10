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
```

### Step 2: (Optional) Enable Database Schema Detection
If you want AutoTestify to detect unique fields from your database schema, install doctrine/dbal:

```bash
composer require doctrine/dbal
```

Without this, the package will rely on the $unique property in your models.

## Usage
### Generating a Test File
Run the following Artisan command to generate a test file for a specific model:

```bash 
php artisan make:test-model ModelName
```

For example, to generate tests for a User model:

```bash
php artisan make:test-model User
```

This will create tests/Feature/UserTest.php with CRUD and uniqueness tests.

### Example Model Configuration
Define $fillable and (optionally) $unique properties in your model:


```php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ['name', 'email', 'password'];
    protected $unique = ['email']; // Optional if not using database detection
}
```