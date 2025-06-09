<?php

namespace App\Http\Requests;

use App\Enums\SubscriberStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkSubscriberRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'action' => ['required', 'string', 'in:delete,update_status,add_to_list,remove_from_list'],
      'ids' => ['required', 'array'],
      'ids.*' => [
        'required',
        Rule::exists('subscribers', 'id')->where(function ($query) {
          $query->where('team_id', $this->user()->currentTeam->id);
        })
      ],
      'status' => [
        Rule::requiredIf($this->action === 'update_status'),
        Rule::enum(SubscriberStatus::class)
      ],
      'list_id' => [
        Rule::requiredIf(in_array($this->action, ['add_to_list', 'remove_from_list'])),
        Rule::exists('mailing_lists', 'id')->where(function ($query) {
          $query->where('team_id', $this->user()->currentTeam->id);
        })
      ],
    ];
  }
}
