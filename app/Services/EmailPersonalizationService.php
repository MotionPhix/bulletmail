<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;

class EmailPersonalizationService
{
  protected $defaultVariables = [];
  protected $customVariables = [];
  protected $systemVariables = [];

  public function __construct()
  {
    $this->initializeDefaultVariables();
    $this->initializeSystemVariables();
  }

  protected function initializeDefaultVariables()
  {
    $this->defaultVariables = [
      'date' => Carbon::now()->format('F j, Y'),
      'time' => Carbon::now()->format('g:i A'),
      'year' => Carbon::now()->format('Y'),
      'unsubscribe_url' => '{{unsubscribe_url}}', // SendGrid will replace this
      'web_version_url' => '{{weblink}}', // SendGrid will replace this
    ];
  }

  protected function initializeSystemVariables()
  {
    $this->systemVariables = [
      'subscriber' => [
        'first_name' => 'Subscriber first name',
        'last_name' => 'Subscriber last name',
        'email' => 'Subscriber email address',
        'company' => 'Subscriber company name',
      ],
      'sender' => [
        'name' => 'Sender name',
        'email' => 'Sender email address',
        'company' => 'Sender company name',
      ],
      'campaign' => [
        'subject' => 'Email subject',
        'name' => 'Campaign name',
        'description' => 'Campaign description',
      ],
    ];
  }

  public function getAvailableVariables(): array
  {
    return [
      'system' => $this->getSystemVariables(),
      'default' => $this->getDefaultVariables(),
      'custom' => $this->customVariables,
    ];
  }

  public function getSystemVariables(): array
  {
    return $this->systemVariables;
  }

  public function getDefaultVariables(): array
  {
    return $this->defaultVariables;
  }

  public function setCustomVariables(array $variables): self
  {
    $this->customVariables = $variables;
    return $this;
  }

  public function parseTemplate(string $content, array $recipientData = []): string
  {
    $variables = array_merge(
      $this->defaultVariables,
      $this->customVariables,
      $this->flattenVariables($recipientData)
    );

    return preg_replace_callback(
      '/\{\{([^}]+)\}\}/',
      function ($matches) use ($variables) {
        $key = trim($matches[1]);
        return $variables[$key] ?? $matches[0];
      },
      $content
    );
  }

  protected function flattenVariables(array $variables, string $prefix = ''): array
  {
    $result = [];

    foreach ($variables as $key => $value) {
      $newKey = $prefix ? "{$prefix}.{$key}" : $key;

      if (is_array($value)) {
        $result = array_merge($result, $this->flattenVariables($value, $newKey));
      } else {
        $result[$newKey] = $value;
      }
    }

    return $result;
  }

  public function validateTemplate(string $content): array
  {
    $errors = [];
    preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);

    $usedVariables = array_unique($matches[1]);
    $availableVariables = array_keys(array_merge(
      $this->defaultVariables,
      $this->customVariables,
      $this->flattenVariables($this->systemVariables)
    ));

    foreach ($usedVariables as $variable) {
      $variable = trim($variable);
      if (!in_array($variable, $availableVariables)) {
        $errors[] = "Unknown variable: {$variable}";
      }
    }

    return $errors;
  }

  public function replaceVariables(string $content, array $data): string
  {
    return $this->parseTemplate($content, $data);
  }
}
