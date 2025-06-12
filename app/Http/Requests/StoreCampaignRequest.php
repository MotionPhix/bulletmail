<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  public function rules(): array
  {
    return [
      'name' => ['required', 'string', 'max:255'],
      'subject' => ['required', 'string', 'max:255'],
      'preview_text' => ['nullable', 'string', 'max:255'],
      'template_id' => ['required', 'exists:email_templates,id'],
      'content' => ['required', 'array'],
      'content.design' => ['required', 'array'],
      'content.html' => ['required', 'string'],
      'from_name' => ['required', 'string', 'max:255'],
      'from_email' => ['required', 'email'],
      'reply_to' => ['nullable', 'email'],
      'list_ids' => ['required', 'array', 'min:1'],
      'list_ids.*' => ['exists:mailing_lists,id'],
      'scheduled_at' => ['nullable', 'date', 'after:now'],
    ];
  }

  public function messages(): array
  {
    return [
      'name.required' => 'Please provide a campaign name.',
      'subject.required' => 'Email subject line is required.',
      'template_id.required' => 'Please select an email template.',
      'template_id.exists' => 'The selected template does not exist.',
      'content.required' => 'Campaign content cannot be empty.',
      'from_name.required' => 'Sender name is required.',
      'from_email.required' => 'Sender email is required.',
      'from_email.email' => 'Please provide a valid sender email address.',
      'reply_to.email' => 'Please provide a valid reply-to email address.',
      'list_ids.required' => 'Please select at least one mailing list.',
      'list_ids.min' => 'Please select at least one mailing list.',
      'scheduled_at.after' => 'Schedule time must be in the future.',
    ];
  }
}
