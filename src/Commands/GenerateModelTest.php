<?php

namespace Hidayetov\AutoTestify\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;

class GenerateModelTest extends Command
{
    protected $signature = 'make:test-model {model}';
    protected $description = 'Generates a test file for a given model';

    public function handle()
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

    protected function getTestStub($model, $modelClass)
    {
        $fillable = $this->getFillableFields($modelClass);
        $attributes = $this->generateAttributes($fillable);
        $uniqueFields = $this->getUniqueFields($modelClass);

        $stub = "<?php\n\n";
        $stub .= "namespace Tests\Feature;\n\n";
        $stub .= "use Illuminate\Foundation\Testing\RefreshDatabase;\n";
        $stub .= "use Illuminate\Support\Facades\Hash;\n";
        $stub .= "use Tests\TestCase;\n\n";
        $stub .= "class {$model}Test extends TestCase\n";
        $stub .= "{\n";
        $stub .= "    use RefreshDatabase;\n\n";
        $stub .= "    public function test_{$this->snakeCase($model)}_can_be_created_with_fillable_fields()\n";
        $stub .= "    {\n";
        $stub .= "        \$attributes = [\n";
        foreach ($attributes as $key => $value) {
            $stub .= "            '{$key}' => " . var_export($value, true) . ",\n";
        }
        $stub .= "        ];\n";
        $stub .= "        \${$this->camelCase($model)} = {$modelClass}::create(\$attributes);\n";
        foreach ($fillable as $field) {
            if ($field === 'password') {
                $stub .= "        \$this->assertTrue(Hash::check('password', \${$this->camelCase($model)}->{$field}));\n";
            } else {
                $stub .= "        \$this->assertEquals(\$attributes['{$field}'], \${$this->camelCase($model)}->{$field});\n";
            }
        }
        $stub .= "    }\n";

        foreach ($uniqueFields as $field) {
            if (in_array($field, $fillable)) {
                $stub .= "\n    public function test_{$this->snakeCase($model)}_{$this->snakeCase($field)}_must_be_unique()\n";
                $stub .= "    {\n";
                $stub .= "        \$attributes = [\n";
                foreach ($attributes as $key => $value) {
                    $stub .= "            '{$key}' => " . var_export($value, true) . ",\n";
                }
                $stub .= "        ];\n";
                $stub .= "        {$modelClass}::create(\$attributes);\n";
                $stub .= "        \$this->assertDatabaseCount('" . $this->snakeCase($model) . "s', 1);\n";
                $stub .= "        \$this->expectException(\\Illuminate\\Database\\QueryException::class);\n";
                $stub .= "        {$modelClass}::create(\$attributes);\n";
                $stub .= "    }\n";
            }
        }

        $stub .= "}\n";
        return $stub;
    }

    protected function getFillableFields($modelClass)
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

    protected function getUniqueFields($modelClass)
    {
        $table = (new $modelClass())->getTable();

        try {
            DB::connection()->getPdo();
            if (class_exists(\Doctrine\DBAL\Connection::class) && Schema::hasTable($table)) {
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
            }
        } catch (\Exception $e) {
            $this->warn("Database connection unavailable or table not migrated: " . $e->getMessage());
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

    protected function generateAttributes($fillable)
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

    protected function snakeCase($value)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
    }

    protected function camelCase($value)
    {
        return lcfirst($value);
    }
}