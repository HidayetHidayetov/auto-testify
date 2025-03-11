<?php

namespace Hidayetov\AutoTestify\Commands;

use Hidayetov\AutoTestify\Helpers\TestUtils;
use Hidayetov\AutoTestify\Services\ValidationTestGenerator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

class GenerateModelTest extends Command
{
    protected $signature = 'make:test-model {model}';
    protected $description = 'Generates a test file for a given model';

    public function handle(): void
    {
        $model = $this->argument('model');
        $this->info("Generating test file for {$model}...");

        $modelClass = "\\App\\Models\\{$model}";
        if (!class_exists($modelClass)) {
            $this->error("Model {$modelClass} not found!");
            return;
        }

        $testPath = base_path("tests/Feature/{$model}Test.php");
        if (File::exists($testPath)) {
            $this->warn("Test file for {$model} already exists at {$testPath}!");
            return;
        }

        $stub = $this->getTestStub($model, $modelClass);
        File::put($testPath, $stub);
        $this->info("Test file created successfully at: {$testPath}");
    }

    protected function getTestStub(string $model, string $modelClass): string
    {
        $fillable = $this->getFillableFields($modelClass);
        $attributes = $this->generateAttributes($fillable);
        $uniqueFields = $this->getUniqueFields($modelClass);
        $isSoftDeletable = $this->isSoftDeletable($modelClass);
        $rules = $this->getValidationRules($modelClass);

        $stub = "<?php\n\n";
        $stub .= "namespace Tests\Feature;\n\n";
        $stub .= "use Illuminate\Foundation\Testing\RefreshDatabase;\n";
        $stub .= "use Illuminate\Support\Facades\Hash;\n";
        $stub .= "use Illuminate\Support\Facades\Validator;\n";
        $stub .= "use Tests\TestCase;\n\n";
        $stub .= "class {$model}Test extends TestCase\n";
        $stub .= "{\n";
        $stub .= "    use RefreshDatabase;\n\n";
        $stub .= "    protected \$rules = " . var_export($rules, true) . ";\n\n";

        $stub .= "    public function test_" . TestUtils::snakeCase($model) . "_can_be_created_with_fillable_fields()\n";
        $stub .= "    {\n";
        $stub .= "        \$attributes = [\n";
        foreach ($attributes as $key => $value) {
            $stub .= "            '{$key}' => " . var_export($value, true) . ",\n";
        }
        $stub .= "        ];\n";
        $stub .= "        \$" . TestUtils::camelCase($model) . " = {$modelClass}::create(\$attributes);\n";
        foreach ($fillable as $field) {
            if ($field === 'password') {
                $stub .= "        \$this->assertTrue(Hash::check('password', \$" . TestUtils::camelCase($model) . "->{$field}));\n";
            } else {
                $stub .= "        \$this->assertEquals(\$attributes['{$field}'], \$" . TestUtils::camelCase($model) . "->{$field});\n";
            }
        }
        $stub .= "    }\n";

        $stub .= "\n    public function test_" . TestUtils::snakeCase($model) . "_can_be_retrieved()\n";
        $stub .= "    {\n";
        $stub .= "        \$attributes = [\n";
        foreach ($attributes as $key => $value) {
            $stub .= "            '{$key}' => " . var_export($value, true) . ",\n";
        }
        $stub .= "        ];\n";
        $stub .= "        \$" . TestUtils::camelCase($model) . " = {$modelClass}::create(\$attributes);\n";
        $stub .= "        \$retrieved = {$modelClass}::find(\$" . TestUtils::camelCase($model) . "->id);\n";
        $stub .= "        \$this->assertNotNull(\$retrieved);\n";
        foreach ($fillable as $field) {
            if ($field !== 'password') {
                $stub .= "        \$this->assertEquals(\$" . TestUtils::camelCase($model) . "->{$field}, \$retrieved->{$field});\n";
            }
        }
        $stub .= "    }\n";

        $stub .= "\n    public function test_" . TestUtils::snakeCase($model) . "_can_be_updated()\n";
        $stub .= "    {\n";
        $stub .= "        \$attributes = [\n";
        foreach ($attributes as $key => $value) {
            $stub .= "            '{$key}' => " . var_export($value, true) . ",\n";
        }
        $stub .= "        ];\n";
        $stub .= "        \$" . TestUtils::camelCase($model) . " = {$modelClass}::create(\$attributes);\n";
        $stub .= "        \$updatedAttributes = [\n";
        foreach ($fillable as $key) {
            if ($key === 'password') {
                $stub .= "            '{$key}' => Hash::make('newpassword'),\n"; // Şifrələnmiş dəyər
            } elseif (str_contains($key, 'email')) {
                $stub .= "            '{$key}' => 'updated@example.com',\n";
            } else {
                $stub .= "            '{$key}' => 'Updated {$key}',\n";
            }
        }
        $stub .= "        ];\n";
        $stub .= "        \$" . TestUtils::camelCase($model) . "->update(\$updatedAttributes);\n";
        foreach ($fillable as $field) {
            if ($field === 'password') {
                $stub .= "        \$this->assertTrue(Hash::check('newpassword', \$" . TestUtils::camelCase($model) . "->{$field}));\n";
            } else {
                $stub .= "        \$this->assertEquals(\$updatedAttributes['{$field}'], \$" . TestUtils::camelCase($model) . "->{$field});\n";
            }
        }
        $stub .= "    }\n";

        $stub .= "\n    public function test_" . TestUtils::snakeCase($model) . "_can_be_deleted()\n";
        $stub .= "    {\n";
        $stub .= "        \$attributes = [\n";
        foreach ($attributes as $key => $value) {
            $stub .= "            '{$key}' => " . var_export($value, true) . ",\n";
        }
        $stub .= "        ];\n";
        $stub .= "        \$" . TestUtils::camelCase($model) . " = {$modelClass}::create(\$attributes);\n";
        $stub .= "        \$" . TestUtils::camelCase($model) . "->delete();\n";
        if ($isSoftDeletable) {
            $stub .= "        \$this->assertSoftDeleted('" . TestUtils::snakeCase($model) . "s', ['id' => \$" . TestUtils::camelCase($model) . "->id]);\n";
        } else {
            $stub .= "        \$this->assertDatabaseMissing('" . TestUtils::snakeCase($model) . "s', ['id' => \$" . TestUtils::camelCase($model) . "->id]);\n";
        }
        $stub .= "    }\n";

        foreach ($uniqueFields as $field) {
            if (in_array($field, $fillable)) {
                $stub .= "\n    public function test_" . TestUtils::snakeCase($model) . "_" . TestUtils::snakeCase($field) . "_must_be_unique()\n";
                $stub .= "    {\n";
                $stub .= "        \$attributes = [\n";
                foreach ($attributes as $key => $value) {
                    $stub .= "            '{$key}' => " . var_export($value, true) . ",\n";
                }
                $stub .= "        ];\n";
                $stub .= "        {$modelClass}::create(\$attributes);\n";
                $stub .= "        \$this->assertDatabaseCount('" . TestUtils::snakeCase($model) . "s', 1);\n";
                $stub .= "        \$this->expectException(\\Illuminate\\Database\\QueryException::class);\n";
                $stub .= "        {$modelClass}::create(\$attributes);\n";
                $stub .= "    }\n";
            }
        }

        if (!empty($rules)) {
            $validationGenerator = new ValidationTestGenerator();
            $validationTests = $validationGenerator->generateTests($model, $rules, $attributes);
            foreach ($validationTests as $test) {
                $stub .= "\n" . $test['body'];
            }
        }

        $stub .= "}\n";
        return $stub;
    }

    protected function getFillableFields(string $modelClass): array
    {
        try {
            $reflection = new ReflectionClass($modelClass);
            $property = $reflection->getProperty('fillable');
            $property->setAccessible(true);
            return $property->getValue(new $modelClass());
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getUniqueFields(string $modelClass): array
    {
        $table = (new $modelClass())->getTable();

        if (class_exists(\Doctrine\DBAL\Connection::class) && Schema::hasTable($table)) {
            try {
                $schemaManager = DB::getDoctrineSchemaManager();
                $indexes = $schemaManager->listTableIndexes($table);

                $uniqueFields = [];
                foreach ($indexes as $index) {
                    if ($index->isUnique() && !$index->isPrimary()) {
                        $columns = $index->getColumns();
                        if (count($columns) === 1) {
                            $uniqueFields[] = $columns[0];
                        }
                    }
                }
                return $uniqueFields;
            } catch (\Exception $e) {
                $this->warn("Could not retrieve unique fields from database: " . $e->getMessage());
            }
        }

        try {
            $reflection = new ReflectionClass($modelClass);
            if ($reflection->hasProperty('unique')) {
                $property = $reflection->getProperty('unique');
                $property->setAccessible(true);
                return $property->getValue(new $modelClass());
            }
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function isSoftDeletable(string $modelClass): bool
    {
        return in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($modelClass));
    }

    protected function getValidationRules(string $modelClass): array
    {
        try {
            $reflection = new ReflectionClass($modelClass);
            if ($reflection->hasProperty('rules')) {
                $property = $reflection->getProperty('rules');
                $property->setAccessible(true);
                return $property->getValue();
            }
            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function generateAttributes(array $fillable): array
    {
        $attributes = [];
        foreach ($fillable as $field) {
            if (str_contains($field, 'name') || str_contains($field, 'title')) {
                $attributes[$field] = 'Test ' . ucfirst($field);
            } elseif (str_contains($field, 'email')) {
                $attributes[$field] = 'test@example.com';
            } elseif (str_contains($field, 'password')) {
                $attributes[$field] = 'password';
            } elseif (str_contains($field, 'slug')) {
                $attributes[$field] = 'test-' . $field;
            } else {
                $attributes[$field] = 'Sample ' . $field;
            }
        }
        return $attributes;
    }
}