<?php

namespace App\Http\Requests\Organization\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateIntegrationsSettingsRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()->can('update', $this->organization);
  }

  public function rules(): array
  {
    return [
      'integrations' => ['required', 'array'],

      // SMTP Settings (Always Available)
      'integrations.smtp' => ['required', 'array'],
      'integrations.smtp.enabled' => ['required', 'boolean'],
      'integrations.smtp.host' => ['required_if:integrations.smtp.enabled,true', 'string'],
      'integrations.smtp.port' => ['required_if:integrations.smtp.enabled,true', 'integer', 'between:1,65535'],
      'integrations.smtp.username' => ['required_if:integrations.smtp.enabled,true', 'string'],
      'integrations.smtp.password' => ['required_if:integrations.smtp.enabled,true', 'string'],
      'integrations.smtp.encryption' => ['nullable', 'string', 'in:tls,ssl'],
      'integrations.smtp.from_address' => ['required_if:integrations.smtp.enabled,true', 'email'],
      'integrations.smtp.from_name' => ['required_if:integrations.smtp.enabled,true', 'string', 'max:255'],

      // Premium Email Services (Require Paid Plan)
      'integrations.sendgrid' => ['required', 'array'],
      'integrations.sendgrid.enabled' => [
        'required',
        'boolean',
        function ($attribute, $value, $fail) {
          if ($value && !$this->organization->subscription?->plan->hasFeature('premium_email_services')) {
            $fail('SendGrid integration requires a paid subscription.');
          }
        }
      ],
      'integrations.sendgrid.api_key' => [
        'required_if:integrations.sendgrid.enabled,true',
        'string',
        'starts_with:SG.',
        'min:50',
      ],

      // Mailgun Integration
      'integrations.mailgun' => ['required', 'array'],
      'integrations.mailgun.enabled' => ['required', 'boolean'],
      'integrations.mailgun.api_key' => [
        'required_if:integrations.mailgun.enabled,true',
        'string',
        'starts_with:key-',
        'min:50',
      ],
      'integrations.mailgun.domain' => [
        'required_if:integrations.mailgun.enabled,true',
        'string',
        'regex:/^([a-z0-9]+(-[a-z0-9]+)*\.)+[a-z]{2,}$/',
      ],

      // Stripe Integration
      'integrations.stripe' => ['required', 'array'],
      'integrations.stripe.enabled' => ['required', 'boolean'],
      'integrations.stripe.public_key' => [
        'required_if:integrations.stripe.enabled,true',
        'string',
        'starts_with:pk_',
        'min:20',
      ],
      'integrations.stripe.secret_key' => [
        'required_if:integrations.stripe.enabled,true',
        'string',
        'starts_with:sk_',
        'min:20',
      ],
    ];
  }

  public function messages(): array
  {
    return [
      // SMTP Messages
      'integrations.smtp.host.required_if' => 'SMTP host is required when SMTP is enabled.',
      'integrations.smtp.port.required_if' => 'SMTP port is required when SMTP is enabled.',
      'integrations.smtp.port.between' => 'SMTP port must be between 1 and 65535.',
      'integrations.smtp.username.required_if' => 'SMTP username is required when SMTP is enabled.',
      'integrations.smtp.password.required_if' => 'SMTP password is required when SMTP is enabled.',
      'integrations.smtp.from_address.required_if' => 'From email address is required when SMTP is enabled.',
      'integrations.smtp.from_name.required_if' => 'From name is required when SMTP is enabled.',

      // SendGrid Messages
      'integrations.sendgrid.api_key.required_if' => 'A SendGrid API key is required when SendGrid integration is enabled.',
      'integrations.sendgrid.api_key.starts_with' => 'The SendGrid API key must start with "SG."',
      'integrations.sendgrid.api_key.min' => 'The SendGrid API key appears to be invalid. Please check your API key.',

      // Mailgun Messages
      'integrations.mailgun.api_key.required_if' => 'A Mailgun API key is required when Mailgun integration is enabled.',
      'integrations.mailgun.api_key.starts_with' => 'The Mailgun API key must start with "key-"',
      'integrations.mailgun.api_key.min' => 'The Mailgun API key appears to be invalid. Please check your API key.',
      'integrations.mailgun.domain.required_if' => 'A domain is required when Mailgun integration is enabled.',
      'integrations.mailgun.domain.regex' => 'Please enter a valid domain name.',

      // Stripe Messages
      'integrations.stripe.public_key.required_if' => 'A Stripe public key is required when Stripe integration is enabled.',
      'integrations.stripe.public_key.starts_with' => 'The Stripe public key must start with "pk_"',
      'integrations.stripe.public_key.min' => 'The Stripe public key appears to be invalid. Please check your key.',
      'integrations.stripe.secret_key.required_if' => 'A Stripe secret key is required when Stripe integration is enabled.',
      'integrations.stripe.secret_key.starts_with' => 'The Stripe secret key must start with "sk_"',
      'integrations.stripe.secret_key.min' => 'The Stripe secret key appears to be invalid. Please check your key.',
    ];
  }

  public function attributes(): array
  {
    return [
      'integrations.sendgrid.enabled' => 'SendGrid integration',
      'integrations.sendgrid.api_key' => 'SendGrid API key',
      'integrations.mailgun.enabled' => 'Mailgun integration',
      'integrations.mailgun.api_key' => 'Mailgun API key',
      'integrations.mailgun.domain' => 'Mailgun domain',
      'integrations.stripe.enabled' => 'Stripe integration',
      'integrations.stripe.public_key' => 'Stripe public key',
      'integrations.stripe.secret_key' => 'Stripe secret key',
    ];
  }
}
