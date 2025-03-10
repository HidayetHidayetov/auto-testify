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
If you want AutoTestify to detect unique fields from your database schema, install `doctrine/dbal`:

```bash
composer require doctrine/dbal
```

Without this, the package will rely on the `$unique` property in your models.

---

## Usage

### Generating a Test File
Run the following Artisan command to generate a test file for a specific model:

```bash
php artisan make:test-model ModelName
```

For example, to generate tests for a `User` model:

```bash
php artisan make:test-model User
```

This will create `tests/Feature/UserTest.php` with CRUD and uniqueness tests.

### Example Model Configuration
Define `$fillable` and (optionally) `$unique` properties in your model:

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ['name', 'email', 'password'];
    protected $unique = ['email']; // Optional if not using database detection
}
```

### Generated Tests
For the `User` model above, the generated `UserTest.php` might look like this:

```php
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_created_with_fillable_fields()
    {
        $attributes = [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'password' => 'password',
        ];
        $user = \App\Models\User::create($attributes);
        $this->assertEquals($attributes['name'], $user->name);
        $this->assertEquals($attributes['email'], $user->email);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_user_can_be_retrieved()
    {
        $attributes = [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'password' => 'password',
        ];
        $user = \App\Models\User::create($attributes);
        $retrieved = \App\Models\User::find($user->id);
        $this->assertNotNull($retrieved);
        $this->assertEquals($attributes['name'], $retrieved->name);
        $this->assertEquals($attributes['email'], $retrieved->email);
        $this->assertTrue(Hash::check('password', $retrieved->password));
    }

    public function test_user_can_be_updated()
    {
        $attributes = [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'password' => 'password',
        ];
        $user = \App\Models\User::create($attributes);
        $updatedAttributes = [
            'name' => 'Updated Test Name',
            'email' => 'test@example.com',
            'password' => 'newpassword',
        ];
        $user->update($updatedAttributes);
        $this->assertEquals($updatedAttributes['name'], $user->name);
        $this->assertEquals($updatedAttributes['email'], $user->email);
        $this->assertTrue(Hash::check('newpassword', $user->password));
    }

    public function test_user_can_be_deleted()
    {
        $attributes = [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'password' => 'password',
        ];
        $user = \App\Models\User::create($attributes);
        $user->delete();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_user_email_must_be_unique()
    {
        $attributes = [
            'name' => 'Test Name',
            'email' => 'test@example.com',
            'password' => 'password',
        ];
        \App\Models\User::create($attributes);
        $this->assertDatabaseCount('users', 1);
        $this->expectException(\Illuminate\Database\QueryException::class);
        \App\Models\User::create($attributes);
    }
}
```

---

## Configuration

### Model Setup
- **`$fillable`**: Required to specify fields that can be mass-assigned.
- **`$unique`**: Optional; define unique fields if not relying on database schema detection.

### Database Detection
Ensure your database is migrated (`php artisan migrate`) and `doctrine/dbal` is installed for automatic unique field detection.

---

## Running Tests

After generating the test files, run your tests with:

```bash
php artisan test
```

### Example Output
```
PASS  Tests\Feature\UserTest
✓ user can be created with fillable fields
✓ user can be retrieved
✓ user can be updated
✓ user can be deleted
✓ user email must be unique
```

---

## Troubleshooting

- **"No connection could be made" Error**: Ensure your `.env` file is configured correctly and your database server is running.
    - Example `.env` for MySQL:
      ```
      DB_CONNECTION=mysql
      DB_HOST=127.0.0.1
      DB_PORT=3306
      DB_DATABASE=your_database
      DB_USERNAME=root
      DB_PASSWORD=
      ```
    - Or use SQLite:
      ```
      DB_CONNECTION=sqlite
      DB_DATABASE=/path/to/database.sqlite
      ```
- **Unique Tests Not Generated**: Define `$unique` in your model or install `doctrine/dbal` and migrate your database.

---

## License

This package is open-sourced under the This package is open-sourced under the [MIT License](LICENSE).

---

## Author

- **Hidayet Hidayetov** - [GitHub](https://github.com/HidayetHidayetov)
- Special thanks to contributors and collaborators!

---

Happy testing with AutoTestify! 🚀