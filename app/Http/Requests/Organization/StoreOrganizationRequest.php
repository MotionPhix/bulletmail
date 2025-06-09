<?php

namespace App\Http\Requests\Organization;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrganizationRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name' => ['required', 'string', 'max:255'],
      'size' => ['required', 'string'],
      'industry' => ['required', 'string'],
      'website' => ['nullable', 'url'],
      'phone' => ['nullable', 'string'],
      'primary_color' => ['nullable', 'string'],
      'secondary_color' => ['nullable', 'string'],
      'default_from_name' => ['nullable', 'string'],
      'default_from_email' => ['nullable', 'email'],
      'default_reply_to' => ['nullable', 'email'],
    ];
  }
}
