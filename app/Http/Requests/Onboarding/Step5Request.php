<?php

namespace App\Http\Requests\Onboarding;

use App\Models\EmailTemplate;

class Step5Request extends BaseStepRequest
{
  public function stepRules(): array
  {
    return [
      'data.template' => ['required', 'array'],
      'data.template.name' => ['required', 'string', 'max:255'],
      'data.template.description' => ['nullable', 'string', 'max:500'],
      'data.template.subject' => ['required', 'string', 'max:255'],
      'data.template.preview_text' => ['nullable', 'string', 'max:255'],
      'data.template.content' => ['required', 'string', 'min:50'],
      'data.template.category' => ['required', 'string', 'in:' . implode(',', EmailTemplate::CATEGORIES)],
      'data.template.type' => ['required', 'string', 'in:' . implode(',', EmailTemplate::TYPES)],
      'data.template.is_default' => ['boolean'],
      'data.template.variables' => ['nullable', 'array'],
      'data.template.design' => ['nullable', 'array'],
      'data.template.tags' => ['nullable', 'array'],
      'data.template.tags.*' => ['string', 'max:50'],
    ];
  }

  public function messages(): array
  {
    return [
      'data.template.name.required' => 'Please provide a name for your template.',
      'data.template.name.max' => 'Template name cannot exceed 255 characters.',
      'data.template.description.max' => 'Description cannot exceed 500 characters.',
      'data.template.subject.required' => 'Email subject line is required.',
      'data.template.subject.max' => 'Subject line cannot exceed 255 characters.',
      'data.template.preview_text.max' => 'Preview text cannot exceed 255 characters.',
      'data.template.content.required' => 'Template content is required.',
      'data.template.content.min' => 'Template content should be at least 50 characters.',
      'data.template.category.required' => 'Please select a template category.',
      'data.template.category.in' => 'Please select a valid template category.',
      'data.template.type.required' => 'Template type is required.',
      'data.template.type.in' => 'Please select a valid template type.',
      'data.template.tags.*.max' => 'Each tag cannot exceed 50 characters.',
    ];
  }
}
