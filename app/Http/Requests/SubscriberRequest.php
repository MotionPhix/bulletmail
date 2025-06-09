<?php

namespace App\Http\Requests;

use App\Enums\SubscriberStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubscriberRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'email' => [
        'required',
        'email',
        Rule::unique('subscribers')->where(function ($query) {
          return $query->where('team_id', $this->user()->currentTeam->id)
            ->whereNull('deleted_at');
        })->ignore($this->subscriber)
      ],
      'first_name' => ['required', 'string', 'max:255'],
      'last_name' => ['required', 'string', 'max:255'],
      'status' => ['sometimes', Rule::enum(SubscriberStatus::class)],
      'custom_fields' => ['nullable', 'array'],
      'custom_fields.*' => ['nullable', 'string', 'max:500'],
      'mailing_lists' => ['nullable', 'array'],
      'mailing_lists.*' => ['exists:mailing_lists,id'],
      'source' => ['nullable', 'string', 'max:50'],
      'metadata' => ['nullable', 'array'],
    ];
  }

  public function messages(): array
  {
    return [
      'email.unique' => 'This email is already subscribed to your team.',
    ];
  }
}
