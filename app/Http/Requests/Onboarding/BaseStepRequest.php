<?php

namespace App\Http\Requests\Onboarding;

use Illuminate\Foundation\Http\FormRequest;

abstract class BaseStepRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  abstract public function stepRules(): array;

  public function rules(): array
  {
    // Merge top-level rules with step-specific rules
    return array_merge(
      [
        'step' => ['required', 'integer', 'between:1,6'],
        'data' => ['required', 'array'],
      ],
      $this->transformStepRules()
    );
  }

  public function messages(): array
  {
    return array_merge(
      [
        'step.required' => 'The step number is required.',
        'step.integer' => 'The step must be a number.',
        'step.between' => 'Invalid step number.',
        'data.required' => 'The step data is required.',
        'data.array' => 'Invalid data format.',
      ],
      $this->stepMessages()
    );
  }

  // Transform step-specific rules for nested data validation
  protected function transformStepRules(): array
  {
    $rules = [];
    foreach ($this->stepRules() as $key => $rule) {
      $rules[$key] = $rule;
    }
    return $rules;
  }

  // Optionally merge step-specific messages
  protected function stepMessages(): array
  {
    return [];
  }

  public function attributes(): array
  {
    return [];
  }
}

