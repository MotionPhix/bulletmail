<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Services\SendGrid\SendGridService;
use App\Enums\TemplateType;

class TemplateService extends BaseService
{
  public function create(array $data): EmailTemplate
  {
    return EmailTemplate::create(array_merge($data, [
      'team_id' => auth()->user()->current_team_id,
      'user_id' => auth()->id(),
    ]));
  }

  public function update(EmailTemplate $template, array $data): bool
  {
    return $template->update($data);
  }

  public function delete(EmailTemplate $template): ?bool
  {
    if ($template->isInUse()) {
      throw new \Exception('Template is in use by campaigns and cannot be deleted.');
    }

    return $template->delete();
  }
}
