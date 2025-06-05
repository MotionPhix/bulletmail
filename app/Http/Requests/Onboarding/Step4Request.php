<?php

namespace App\Http\Requests\Onboarding;

class Step4Request extends BaseStepRequest
{
  public function stepRules(): array
  {
    return [
      'data.domain' => ['required', 'string', 'regex:/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/i'],
      'data.dkim_selector' => ['required', 'string', 'alpha_dash', 'max:50'],
      'data.tracking_domain' => ['nullable', 'string', 'regex:/^(?!:\/\/)(?=.{1,255}$)((.{1,63}\.){1,127}(?![0-9]*$)[a-z0-9-]+\.?)$/i'],
      'data.verify_domain' => ['required', 'boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'data.domain.required' => 'Please enter a sending domain.',
      'data.domain.regex' => 'Please enter a valid domain name.',
      'data.dkim_selector.required' => 'DKIM selector is required for email authentication.',
      'data.dkim_selector.alpha_dash' => 'DKIM selector can only contain letters, numbers, dashes and underscores.',
      'data.tracking_domain.regex' => 'Please enter a valid tracking domain.',
      'data.verify_domain.required' => 'Domain verification is required to proceed.',
    ];
  }
}
