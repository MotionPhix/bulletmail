<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BrandingRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'company_name' => ['sometimes', 'string', 'max:255'],
      'logo' => ['sometimes', 'image', 'max:2048'],
      'primary_color' => ['sometimes', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
      'accent_color' => ['sometimes', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
      'email_header' => ['sometimes', 'nullable', 'string'],
      'email_footer' => ['sometimes', 'nullable', 'string'],
      'custom_css' => ['sometimes', 'nullable', 'string']
    ];
  }
}
