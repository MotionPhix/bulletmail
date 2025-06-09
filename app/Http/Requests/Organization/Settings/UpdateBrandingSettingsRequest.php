<?php

namespace App\Http\Requests\Organization\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBrandingSettingsRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()->can('update', $this->organization);
  }

  public function rules(): array
  {
    return [
      'primary_color' => [
        'required',
        'string',
        'max:7',
        'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'
      ],
      'secondary_color' => [
        'required',
        'string',
        'max:7',
        'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'
      ],
      'email_header' => [
        'nullable',
        'string',
        'max:65535'
      ],
      'email_footer' => [
        'nullable',
        'string',
        'max:65535'
      ],
      'logo' => [
        'nullable',
        'image',
        'max:2048',
        'mimes:jpeg,png,jpg,gif',
        'dimensions:min_width=100,min_height=100,max_width=1000,max_height=1000'
      ]
    ];
  }

  public function messages(): array
  {
    return [
      'primary_color.required' => 'A primary brand color is required.',
      'primary_color.regex' => 'The primary color must be a valid hex color code (e.g. #FF0000).',
      'secondary_color.required' => 'A secondary brand color is required.',
      'secondary_color.regex' => 'The secondary color must be a valid hex color code (e.g. #FF0000).',
      'email_header.max' => 'The email header template is too long.',
      'email_footer.max' => 'The email footer template is too long.',
      'logo.image' => 'The logo must be an image file.',
      'logo.max' => 'The logo may not be larger than 2MB.',
      'logo.mimes' => 'The logo must be a file of type: jpeg, png, jpg, gif.',
      'logo.dimensions' => 'The logo must be between 100x100 and 1000x1000 pixels.'
    ];
  }

  public function attributes(): array
  {
    return [
      'primary_color' => 'primary brand color',
      'secondary_color' => 'secondary brand color',
      'email_header' => 'email header template',
      'email_footer' => 'email footer template',
      'logo' => 'organization logo'
    ];
  }
}
