<?php

namespace Dev\PostmanExporter\Services;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use ReflectionMethod;
use ReflectionNamedType;

class PostmanDataGenerator
{
    /**
     * Map common Laravel validation rules to dummy values for Postman.
     */
    protected array $ruleMap = [
        'string'    => 'Sample text',
        'email'     => 'user@example.com',
        'integer'   => 123,
        'numeric'   => 99.99,
        'boolean'   => true,
        'date'      => '2024-12-31',
        'url'       => 'https://example.com',
        'uuid'      => '550e8400-e29b-41d4-a716-446655440000',
        'password'  => 'secret123',
        'array'     => [],
        'image'     => 'image.jpg',
        'file'      => 'file.pdf',
        'json'      => '{"key": "value"}',
        'ip'        => '127.0.0.1',
    ];

    /**
     * Inspect a controller method to find a FormRequest and generate dummy data.
     */
    public function generateForRoute(string $controller, string $method): array
    {
        try {
            if (!class_exists($controller)) {
                return [];
            }

            $reflection = new ReflectionMethod($controller, $method);
            
            foreach ($reflection->getParameters() as $parameter) {
                $type = $parameter->getType();
                
                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    $className = $type->getName();
                    
                    if (is_subclass_of($className, FormRequest::class)) {
                        return $this->extractFromFormRequest($className);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail if reflection is not possible
        }

        return [];
    }

    /**
     * Extract rules from a FormRequest class and map them to sample data.
     */
    protected function extractFromFormRequest(string $formRequestClass): array
    {
        try {
            $instance = new $formRequestClass();
            
            if (!method_exists($instance, 'rules')) {
                return [];
            }

            $rules = $instance->rules();
            $data = [];

            foreach ($rules as $field => $fieldRules) {
                // If it's a nested attribute (e.g., 'user.name'), skip for simplicity or handle later
                if (Str::contains($field, '.')) {
                    continue;
                }

                // Convert pipe string to array
                if (is_string($fieldRules)) {
                    $fieldRules = explode('|', $fieldRules);
                }

                $data[$field] = $this->guessValueForRules($field, (array) $fieldRules);
            }

            return $data;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Guess a realistic value based on the field name and validation rules.
     */
    protected function guessValueForRules(string $field, array $rules): mixed
    {
        foreach ($rules as $rule) {
            $ruleName = is_string($rule) ? explode(':', $rule)[0] : '';
            $ruleName = strtolower($ruleName);

            if (isset($this->ruleMap[$ruleName])) {
                return $this->ruleMap[$ruleName];
            }
        }

        // Fallback to guessing by field name
        $lowerField = strtolower($field);
        if (Str::contains($lowerField, 'email')) return $this->ruleMap['email'];
        if (Str::contains($lowerField, ['id', 'count', 'amount', 'qty', 'page', 'limit'])) return 1;
        if (Str::contains($lowerField, ['is_', 'has_', 'active'])) return true;
        if (Str::contains($lowerField, ['password', 'secret', 'token'])) return $this->ruleMap['password'];
        if (Str::contains($lowerField, ['date', 'time', 'at'])) return $this->ruleMap['date'];
        
        return "example_" . $field;
    }
}
