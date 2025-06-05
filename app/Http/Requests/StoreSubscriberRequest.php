<?php

namespace App\Http\Requests;

use App\Models\Subscriber;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriberRequest extends FormRequest
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
        Rule::unique('subscribers')->where(fn ($query) =>
        $query->where('team_id', $this->user()->currentTeam->id)
        )
      ],
      'first_name' => ['required', 'string', 'max:255'],
      'last_name' => ['required', 'string', 'max:255'],
      'company' => ['nullable', 'string', 'max:255'],
      'status' => [
        'sometimes',
        Rule::in([
          Subscriber::STATUS_SUBSCRIBED,
          Subscriber::STATUS_UNSUBSCRIBED,
          Subscriber::STATUS_BOUNCED,
          Subscriber::STATUS_COMPLAINED
        ])
      ],
      'metadata' => ['sometimes', 'array'],
    ];
  }

  public function validated($key = null, $default = null): array
  {
    $validated = parent::validated();

    return array_merge($validated, [
      'team_id' => $this->user()->currentTeam->id,
      'user_id' => $this->user()->id,
      'status' => $validated['status'] ?? Subscriber::STATUS_SUBSCRIBED,
    ]);
  }
}
