<?php

namespace App\Http\Requests\Onboarding;

class Step3Request extends BaseStepRequest
{
  public function stepRules(): array
  {
    return [
      'data.contacts' => ['required', 'array', 'min:1'],
      'data.contacts.*.email' => ['required', 'email', 'distinct'],
      'data.contacts.*.first_name' => ['nullable', 'string', 'max:255'],
      'data.contacts.*.last_name' => ['nullable', 'string', 'max:255'],
    ];
  }

  public function messages(): array
  {
    return [
      'data.contacts.required' => 'Please add at least one contact.',
      'data.contacts.array' => 'Invalid contact data format.',
      'data.contacts.min' => 'Please add at least one contact.',
      'data.contacts.*.email.required' => 'Email is required for each contact.',
      'data.contacts.*.email.email' => 'Please enter a valid email address.',
      'data.contacts.*.email.distinct' => 'Duplicate email addresses are not allowed.',
      'data.contacts.*.first_name.max' => 'First name cannot exceed 255 characters.',
      'data.contacts.*.last_name.max' => 'Last name cannot exceed 255 characters.',
    ];
  }
}
