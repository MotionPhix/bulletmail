<?php

namespace App\Http\Requests\Onboarding;

class Step2Request extends BaseStepRequest
{
  public function stepRules(): array
  {
    return [
      'data.sender_settings' => ['required', 'array'],
      'data.sender_settings.default_sender_name' => ['required', 'string', 'max:255'],
      'data.sender_settings.default_sender_email' => ['required', 'email'],

      'data.email_settings' => ['required', 'array'],
      'data.email_settings.from_name' => ['required', 'string', 'max:255'],
      'data.email_settings.reply_to' => ['required', 'email'],

      'data.preferences' => ['required', 'array'],
      'data.preferences.language' => ['required', 'string', 'in:en,es,fr'],
      'data.preferences.timezone' => ['required', 'string', 'timezone'],
    ];
  }

  public function messages(): array
  {
    return [
      'data.sender_settings.default_sender_name.required' => 'Provide a name for the sender.',
      'data.sender_settings.default_sender_email.required' => 'Provide the sender\'s valid email address.',
      'data.sender_settings.default_sender_email.email' => 'Please enter a valid sender email.',
      'data.email_settings.from_name.required' => 'The from name is required.',
      'data.email_settings.reply_to.required' => 'The reply-to email is required.',
      'data.email_settings.reply_to.email' => 'Please enter a valid reply-to email.',
      'data.preferences.language.in' => 'Please select a valid language.',
      'data.preferences.timezone.timezone' => 'Please select a valid timezone.',
    ];
  }
}
