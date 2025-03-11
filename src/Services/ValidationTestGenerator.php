<?php

namespace Hidayetov\AutoTestify\Services;

use Hidayetov\AutoTestify\Helpers\TestUtils;

class ValidationTestGenerator
{
    /** @var array<string, array<string, string>> */
    protected $supportedRules = [
        'required' => [
            'template' => "test_{model}_{field}_is_required",
            'attributes_method' => 'getRequiredAttributes',
            'assertions' => "\$this->assertTrue(\$validator->fails());\n        \$this->assertArrayHasKey('{field}', \$validator->errors()->toArray());"
        ],
        'email' => [
            'template' => "test_{model}_{field}_must_be_valid_email",
            'attributes_method' => 'getEmailAttributes',
            'assertions' => "\$this->assertTrue(\$validator->fails());\n        \$this->assertArrayHasKey('{field}', \$validator->errors()->toArray());"
        ],
        'max' => [
            'template' => "test_{model}_{field}_must_not_exceed_{param}_characters",
            'attributes_method' => 'getMaxAttributes',
            'assertions' => "\$this->assertTrue(\$validator->fails());\n        \$this->assertArrayHasKey('{field}', \$validator->errors()->toArray());"
        ],
        'min' => [
            'template' => "test_{model}_{field}_must_be_at_least_{param}_characters",
            'attributes_method' => 'getMinAttributes',
            'assertions' => "\$this->assertTrue(\$validator->fails());\n        \$this->assertArrayHasKey('{field}', \$validator->errors()->toArray());"
        ],
    ];

    /**
     * @param string $model
     * @param array<string, string> $rules
     * @param array<string, string> $attributes
     * @return array<int, array{name: string, body: string}>
     */
    public function generateTests(string $model, array $rules, array $attributes): array
    {
        $tests = [];
        foreach ($rules as $field => $ruleString) {
            $rulesArray = explode('|', $ruleString);
            foreach ($rulesArray as $rule) {
                if (str_contains($rule, ':')) {
                    [$ruleName, $param] = explode(':', $rule);
                } else {
                    $ruleName = $rule;
                    $param = null;
                }

                if (isset($this->supportedRules[$ruleName])) {
                    $ruleConfig = $this->supportedRules[$ruleName];
                    $testName = str_replace(
                        ['{model}', '{field}', '{param}'],
                        [TestUtils::snakeCase($model), TestUtils::snakeCase($field), $param ?? ''],
                        $ruleConfig['template']
                    );

                    $testAttributes = $this->{$ruleConfig['attributes_method']}($field, $attributes, $param);

                    $attributesString = '';
                    foreach ($testAttributes as $key => $value) {
                        $attributesString .= "            '{$key}' => " . var_export($value, true) . ",\n";
                    }

                    $assertions = str_replace('{field}', $field, $ruleConfig['assertions']);

                    $tests[] = [
                        'name' => $testName,
                        'body' => "    public function {$testName}()\n" .
                            "    {\n" .
                            "        \$attributes = [\n" .
                            $attributesString .
                            "        ];\n" .
                            "        \$validator = Validator::make(\$attributes, \$this->rules);\n" .
                            "        {$assertions}\n" .
                            "    }\n"
                    ];
                }
            }
        }
        return $tests;
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     * @param mixed $param
     * @return array<string, string>
     */
    protected function getRequiredAttributes(string $field, array $attributes, $param = null): array
    {
        return array_diff_key($attributes, [$field => '']);
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     * @param mixed $param
     * @return array<string, string>
     */
    protected function getEmailAttributes(string $field, array $attributes, $param = null): array
    {
        return array_merge($attributes, [$field => 'invalid-email']);
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     * @param string|null $param
     * @return array<string, string>
     */
    protected function getMaxAttributes(string $field, array $attributes, ?string $param): array
    {
        return array_merge($attributes, [$field => str_repeat('a', (int)$param + 1)]);
    }

    /**
     * @param string $field
     * @param array<string, string> $attributes
     * @param string|null $param
     * @return array<string, string>
     */
    protected function getMinAttributes(string $field, array $attributes, ?string $param): array
    {
        return array_merge($attributes, [$field => str_repeat('a', (int)$param - 1)]);
    }
}