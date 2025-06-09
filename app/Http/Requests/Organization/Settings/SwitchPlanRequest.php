<?php

namespace App\Http\Requests\Organization\Settings;

use Illuminate\Foundation\Http\FormRequest;

class SwitchPlanRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()->can('update', $this->organization);
  }

  public function rules(): array
  {
    return [
      'plan_id' => ['required', 'exists:plans,id'],
      'payment_method_id' => [
        'required_if:price,>,0',
        'string'
      ]
    ];
  }

  public function messages(): array
  {
    return [
      'plan_id.required' => 'Please select a plan.',
      'plan_id.exists' => 'The selected plan does not exist.',
      'payment_method_id.required_if' => 'Payment method is required for paid plans.'
    ];
  }

  protected function prepareForValidation(): void
  {
    $this->merge([
      'plan_id' => $this->route('plan')->id,
      'organization' => $this->route('organization')
    ]);
  }
}
