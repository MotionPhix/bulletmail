<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Propaganistas\LaravelPhone\PhoneNumber;

class SettingsUpdateRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'sender_settings' => 'required|array',
      'sender_settings.default_sender_name' => 'required|string|max:255',
      'sender_settings.default_sender_email' => 'required|email',

      'company_settings' => 'required|array',
      'company_settings.company_name' => 'nullable|string|max:255',
      'company_settings.industry' => 'nullable|string|max:255',
      'company_settings.company_size' => 'nullable|string',
      'company_settings.role' => 'nullable|string|max:255',
      'company_settings.website' => 'nullable|url',
      'company_settings.phone' => [
        'nullable', 'string',
        'phone:MW,ZA,ZM,ZW', // Allow phone numbers from Malawi and neighboring countries
      ],

      'branding_settings' => 'required|array',
      'branding_settings.logo_url' => 'nullable|string',
      'branding_settings.primary_color' => 'nullable|string',
      'branding_settings.accent_color' => 'nullable|string',
    ];
  }

  protected function prepareForValidation()
  {
    if ($this->company_settings['phone']) {
      try {
        $phone = new PhoneNumber($this->company_settings['phone'], 'MW'); // Default to Malawi

        $this->merge([
          'company_settings' => [
            'phone' => $phone->formatE164(),
          ]
        ]);
      } catch (\Exception $e) {
        // If parsing fails, leave the original value
      }
    }
  }
}
