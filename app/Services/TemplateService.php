<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Services\SendGrid\SendGridService;
use App\Enums\TemplateType;

class TemplateService extends BaseService
{
  public function __construct(
    protected SendGridService $sendGrid,
    protected EmailPersonalizationService $personalization
  ) {}

  public function create(array $data): EmailTemplate
  {
    return $this->transaction(function () use ($data) {
      $template = EmailTemplate::create([
        'team_id' => auth()->user()->currentTeam->id,
        'user_id' => auth()->user()->id,
        'name' => $data['name'],
        'description' => $data['description'] ?? null,
        'subject' => $data['subject'],
        'content' => $data['content'],
        'preview_text' => $data['preview_text'] ?? null,
        'category' => $data['category'],
        'type' => $data['type'],
        'design' => $data['design'] ?? null,
        'variables' => $data['variables'] ?? [],
        'tags' => $data['tags'] ?? []
      ]);

      if ($template->type === TemplateType::HTML) {
        if ($sendgridId = $this->sendGrid->syncTemplate($template)) {
          $template->update(['sendgrid_template_id' => $sendgridId]);
        }
      }

      $this->logActivity($template, 'Created email template');

      return $template;
    });
  }

  public function update(EmailTemplate $template, array $data): EmailTemplate
  {
    $this->validateTeamAccess($template);

    return $this->transaction(function () use ($template, $data) {
      $template->update([
        'name' => $data['name'],
        'description' => $data['description'] ?? $template->description,
        'subject' => $data['subject'],
        'content' => $data['content'],
        'preview_text' => $data['preview_text'] ?? $template->preview_text,
        'category' => $data['category'],
        'design' => $data['design'] ?? $template->design,
        'variables' => $data['variables'] ?? $template->variables,
        'tags' => $data['tags'] ?? $template->tags
      ]);

      if ($template->type === TemplateType::HTML) {
        $this->sendGrid->updateTemplateVersion($template);
      }

      $this->logActivity($template, 'Updated email template');

      return $template->fresh();
    });
  }

  public function duplicate(EmailTemplate $template): EmailTemplate
  {
    $this->validateTeamAccess($template);

    return $this->transaction(function () use ($template) {
      $clone = $template->replicate(['sendgrid_template_id']);
      $clone->name = "{$template->name} (Copy)";
      $clone->save();

      if ($clone->type === TemplateType::HTML) {
        if ($sendgridId = $this->sendGrid->duplicateTemplate($template)) {
          $clone->update(['sendgrid_template_id' => $sendgridId]);
        }
      }

      $this->logActivity($clone, 'Duplicated email template', [
        'original_id' => $template->id
      ]);

      return $clone;
    });
  }

  public function preview(EmailTemplate $template, array $testData = []): string
  {
    $this->validateTeamAccess($template);

    return $this->personalization
      ->setCustomVariables($template->variables)
      ->parseTemplate($template->content, $testData);
  }
}
