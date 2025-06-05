<?php

namespace App\Http\Requests\Onboarding;

class Step6Request extends BaseStepRequest
{
  public function stepRules(): array
  {
    return [
      'data.campaign' => ['required', 'array'],
      'data.campaign.name' => ['required', 'string', 'max:255'],
      'data.campaign.template_id' => ['required', 'exists:templates,id'],
      'data.campaign.test_recipients' => ['required', 'array', 'min:1', 'max:5'],
      'data.campaign.test_recipients.*' => ['required', 'email'],
      'data.campaign.scheduled_for' => ['nullable', 'date', 'after:now'],
      'data.campaign.track_opens' => ['required', 'boolean'],
      'data.campaign.track_clicks' => ['required', 'boolean'],
    ];
  }

  public function messages(): array
  {
    return [
      'data.campaign.name.required' => 'Please provide a name for your test campaign.',
      'data.campaign.template_id.required' => 'Please select a template for your campaign.',
      'data.campaign.template_id.exists' => 'The selected template does not exist.',
      'data.campaign.test_recipients.required' => 'Please add at least one test recipient.',
      'data.campaign.test_recipients.min' => 'Please add at least one test recipient.',
      'data.campaign.test_recipients.max' => 'You can add up to 5 test recipients.',
      'data.campaign.test_recipients.*.email' => 'Please provide valid email addresses for test recipients.',
      'data.campaign.scheduled_for.after' => 'The scheduled time must be in the future.',
    ];
  }
}
