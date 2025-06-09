<?php

namespace App\Http\Requests\Organization\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGeneralSettingsRequest extends FormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   */
  public function authorize(): bool
  {
    return $this->user()->can('update', $this->organization);
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
   */
  public function rules(): array
  {
    return [
      'name' => ['required', 'string', 'max:255'],
      'size' => [
        'required',
        'string',
        Rule::in([
          '1-10',
          '11-50',
          '51-200',
          '201-500',
          '501+'
        ])
      ],
      'industry' => [
        'required',
        'string',
        Rule::in([
          'technology',
          'healthcare',
          'finance',
          'education',
          'retail',
          'manufacturing',
          'other'
        ])
      ],
      'website' => ['nullable', 'url', 'max:255'],
      'phone' => ['nullable', 'string', 'max:50'],
      'default_from_name' => ['nullable', 'string', 'max:255'],
      'default_from_email' => ['nullable', 'email', 'max:255'],
      'default_reply_to' => ['nullable', 'email', 'max:255']
    ];
  }

  public function messages(): array
  {
    return [
      'size.in' => 'The selected company size is invalid.',
      'industry.in' => 'The selected industry is invalid.',
      'website.url' => 'Please enter a valid website URL.',
      'default_from_email.email' => 'Please enter a valid email address for the default sender.',
      'default_reply_to.email' => 'Please enter a valid email address for the reply-to field.'
    ];
  }
}
