<?php

namespace App\Services;

use Illuminate\Support\Str;

class ConditionalContentService
{
  protected $variables = [];
  protected $operators = [
    'AND' => '&&',
    'OR' => '||',
    'NOT' => '!',
    'CONTAINS' => 'contains',
    'STARTS_WITH' => 'starts_with',
    'ENDS_WITH' => 'ends_with',
    'IN' => 'in',
  ];

  public function setVariables(array $variables)
  {
    $this->variables = $variables;
    return $this;
  }

  public function parse(string $content): string
  {
    // Handle nested conditions first
    $content = $this->parseNestedConditions($content);
    return $this->parseConditions($content);
  }

  protected function parseNestedConditions(string $content): string
  {
    $depth = 0;
    $maxDepth = 10; // Prevent infinite recursion

    while (preg_match('/\{%\s*if\s+(.+?)\s*%\}.*?\{%\s*if\s+/s', $content) && $depth < $maxDepth) {
      $content = $this->parseConditions($content);
      $depth++;
    }

    return $content;
  }

  protected function parseConditions(string $content): string
  {
    // Match conditional blocks: {% if condition %}content{% endif %}
    return preg_replace_callback(
      '/\{%\s*if\s+(.+?)\s*%\}(.*?)(?:\{%\s*else\s*%\}(.*?))?\{%\s*endif\s*%\}/s',
      function ($matches) {
        $condition = $this->evaluateCondition($matches[1]);
        return $condition ? $matches[2] : ($matches[3] ?? '');
      },
      $content
    );
  }

  protected function evaluateComplexCondition(string $condition): bool
  {
    // Handle AND conditions
    if (Str::contains($condition, ' AND ')) {
      $parts = array_map('trim', explode(' AND ', $condition));
      return array_reduce($parts, fn($carry, $part) =>
        $carry && $this->evaluateSimpleCondition($part), true);
    }

    // Handle OR conditions
    if (Str::contains($condition, ' OR ')) {
      $parts = array_map('trim', explode(' OR ', $condition));
      return array_reduce($parts, fn($carry, $part) =>
        $carry || $this->evaluateSimpleCondition($part), false);
    }

    // Handle NOT conditions
    if (Str::startsWith(trim($condition), 'NOT ')) {
      return !$this->evaluateSimpleCondition(Str::substr($condition, 4));
    }

    return $this->evaluateSimpleCondition($condition);
  }

  protected function evaluateSimpleCondition(string $condition): bool
  {
    // Handle CONTAINS
    if (Str::contains($condition, ' CONTAINS ')) {
      [$left, $right] = array_map('trim', explode(' CONTAINS ', $condition));
      $leftValue = $this->getVariableValue($left);
      $rightValue = trim($right, '"\'');
      return Str::contains($leftValue, $rightValue);
    }

    // Handle IN
    if (Str::contains($condition, ' IN ')) {
      [$left, $right] = array_map('trim', explode(' IN ', $condition));
      $leftValue = $this->getVariableValue($left);
      $rightValue = json_decode(trim($right, '"\''), true);
      return in_array($leftValue, $rightValue ?? []);
    }

    // Handle basic comparisons
    foreach (['==', '!=', '>=', '<=', '>', '<'] as $operator) {
      if (Str::contains($condition, $operator)) {
        [$left, $right] = array_map('trim', explode($operator, $condition));
        return $this->compareValues($left, $right, $operator);
      }
    }

    // Handle boolean value
    return (bool) $this->getVariableValue($condition);
  }

  protected function evaluateCondition(string $condition): bool
  {
    // Replace variables in condition
    $condition = preg_replace_callback(
      '/\{\{\s*([^}]+)\s*\}\}/',
      fn($m) => $this->getVariableValue(trim($m[1])),
      $condition
    );

    // Basic comparison operators
    if (Str::contains($condition, '==')) {
      [$left, $right] = array_map('trim', explode('==', $condition));
      return $this->compareValues($left, $right, '==');
    }

    if (Str::contains($condition, '!=')) {
      [$left, $right] = array_map('trim', explode('!=', $condition));
      return $this->compareValues($left, $right, '!=');
    }

    if (Str::contains($condition, '>')) {
      [$left, $right] = array_map('trim', explode('>', $condition));
      return $this->compareValues($left, $right, '>');
    }

    if (Str::contains($condition, '<')) {
      [$left, $right] = array_map('trim', explode('<', $condition));
      return $this->compareValues($left, $right, '<');
    }

    // Boolean checks
    return (bool) $this->getVariableValue($condition);
  }

  protected function getVariableValue(string $key)
  {
    if (Str::startsWith($key, '"') || Str::startsWith($key, "'")) {
      return trim($key, '"\'');
    }
    
    return data_get($this->variables, $key, '');
  }

  protected function compareValues($left, $right, string $operator)
  {
    // Remove quotes from string literals
    $left = trim($left, '"\'');
    $right = trim($right, '"\'');

    // Remove quotes from string literals
    $left = is_string($left) ? trim($left, '"\'') : $left;
    $right = is_string($right) ? trim($right, '"\'') : $right;

    switch ($operator) {
      case '==': return $left == $right;
      case '!=': return $left != $right;
      case '>=': return $left >= $right;
      case '<=': return $left <= $right;
      case '>': return $left > $right;
      case '<': return $left < $right;
      default: return false;
    }
  }
}
