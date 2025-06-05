<?php

namespace App\Services\SendGrid;

use SendGrid;
use SendGrid\Mail\Mail;
use App\Models\EmailTemplate;
use App\Models\Campaign;
use App\Models\Subscriber;
use App\Services\NotificationService;
use App\Enums\NotificationType;
use Illuminate\Support\Facades\Log;

class SendGridService
{
  protected SendGrid $client;
  protected string $fromEmail;
  protected string $fromName;
  protected NotificationService $notifications;

  public function __construct(
    string $apiKey,
    string $fromEmail,
    string $fromName,
    NotificationService $notifications
  ) {
    $this->client = new SendGrid($apiKey);
    $this->fromEmail = $fromEmail;
    $this->fromName = $fromName;
    $this->notifications = $notifications;
  }

  public function send(Campaign $campaign, Subscriber $subscriber): bool
  {
    $email = new Mail();

    // Set sender information
    $email->setFrom(
      $campaign->from_email ?? $this->fromEmail,
      $campaign->from_name ?? $this->fromName
    );

    if ($campaign->reply_to) {
      $email->setReplyTo($campaign->reply_to);
    }

    // Set recipient
    $email->addTo($subscriber->email, $subscriber->full_name);

    // Set subject with personalization
    $email->setSubject($this->parsePersonalization($campaign->subject, $subscriber));

    // Set tracking settings
    $settings = $campaign->team->getSetting(TeamSettingType::EMAIL);
    $email->setClickTracking($settings['click_tracking'] ?? true);
    $email->setOpenTracking($settings['open_tracking'] ?? true);

    // Add custom args for webhook tracking
    $email->addCustomArg('campaign_id', (string) $campaign->id);
    $email->addCustomArg('subscriber_id', (string) $subscriber->id);

    // Set template or content
    if ($campaign->template && $campaign->template->sendgrid_template_id) {
      $email->setTemplateId($campaign->template->sendgrid_template_id);
      $email->addDynamicTemplateDatas($this->prepareTemplateData($campaign, $subscriber));
    } else {
      $content = $this->parsePersonalization($campaign->content, $subscriber);
      $email->addContent('text/html', $content);
    }

    try {
      $response = $this->client->send($email);
      $success = $response->statusCode() === 202;

      if (!$success) {
        $this->handleSendError($campaign, $subscriber, $response->body());
      }

      return $success;
    } catch (\Exception $e) {
      $this->handleSendError($campaign, $subscriber, $e->getMessage());
      return false;
    }
  }

  protected function prepareTemplateData(Campaign $campaign, Subscriber $subscriber): array
  {
    return array_merge(
      $campaign->template?->variables ?? [],
      [
        'subscriber' => [
          'email' => $subscriber->email,
          'first_name' => $subscriber->first_name,
          'last_name' => $subscriber->last_name,
          'full_name' => $subscriber->full_name,
          'custom_fields' => $subscriber->custom_fields
        ],
        'campaign' => [
          'name' => $campaign->name,
          'subject' => $campaign->subject
        ],
        'unsubscribe_url' => route('unsubscribe', [
          'campaign' => $campaign->uuid,
          'subscriber' => $subscriber->uuid
        ])
      ]
    );
  }

  protected function parsePersonalization(string $content, Subscriber $subscriber): string
  {
    $replacements = [
      '{{email}}' => $subscriber->email,
      '{{first_name}}' => $subscriber->first_name,
      '{{last_name}}' => $subscriber->last_name,
      '{{full_name}}' => $subscriber->full_name
    ];

    return str_replace(
      array_keys($replacements),
      array_values($replacements),
      $content
    );
  }

  protected function handleSendError(Campaign $campaign, Subscriber $subscriber, string $error): void
  {
    Log::error('SendGrid send failed', [
      'campaign_id' => $campaign->id,
      'subscriber_id' => $subscriber->id,
      'error' => $error
    ]);

    $this->notifications->send(
      $campaign->team,
      NotificationType::CAMPAIGN_FAILED,
      "Failed to send campaign '{$campaign->name}' to {$subscriber->email}",
      ['error' => $error]
    );
  }

  public function syncTemplate(EmailTemplate $template): ?string
  {
    try {
      $response = $this->client->client->templates()->post([
        'name' => $template->name,
        'generation' => 'dynamic',
      ]);

      if ($response->statusCode() === 201) {
        $templateId = json_decode($response->body())->id;

        // Update version with actual content
        $this->updateTemplateVersion($templateId, $template);

        return $templateId;
      }

      Log::error('SendGrid template creation failed', [
        'status' => $response->statusCode(),
        'body' => $response->body(),
      ]);

      return null;
    } catch (\Exception $e) {
      Log::error('SendGrid template sync failed', [
        'error' => $e->getMessage(),
      ]);
      return null;
    }
  }

  public function updateTemplateVersion(EmailTemplate $template): bool
  {
    try {
      if (!$template->sendgrid_template_id) {
        return false;
      }

      $response = $this->client->client->templates()
        ->_($template->sendgrid_template_id)
        ->versions()
        ->post([
          'name' => 'Version ' . time(),
          'subject' => $template->subject,
          'html_content' => $template->content,
          'active' => 1
        ]);

      if ($response->statusCode() === 201) {
        return true;
      }

      Log::error('SendGrid template version update failed', [
        'status' => $response->statusCode(),
        'body' => $response->body(),
        'template_id' => $template->id
      ]);

      return false;
    } catch (\Exception $e) {
      Log::error('SendGrid template version update failed', [
        'error' => $e->getMessage(),
        'template_id' => $template->id
      ]);
      return false;
    }
  }

  public function getTemplate(string $templateId): ?array
  {
    try {
      $response = $this->client->client->templates()->_($templateId)->get();

      if ($response->statusCode() === 200) {
        return json_decode($response->body(), true);
      }

      Log::error('SendGrid template fetch failed', [
        'status' => $response->statusCode(),
        'body' => $response->body()
      ]);

      return null;
    } catch (\Exception $e) {
      Log::error('SendGrid template fetch failed', [
        'error' => $e->getMessage()
      ]);
      return null;
    }
  }

  public function deleteTemplate(string $templateId): bool
  {
    try {
      $response = $this->client->client->templates()->_($templateId)->delete();

      if ($response->statusCode() === 204) {
        return true;
      }

      Log::error('SendGrid template deletion failed', [
        'status' => $response->statusCode(),
        'body' => $response->body()
      ]);

      return false;
    } catch (\Exception $e) {
      Log::error('SendGrid template deletion failed', [
        'error' => $e->getMessage()
      ]);
      return false;
    }
  }

  public function validateTemplate(EmailTemplate $template): array
  {
    $errors = [];

    try {
      $response = $this->client->client->templates()->validate()->post([
        'template' => [
          'name' => $template->name,
          'subject' => $template->subject,
          'html_content' => $template->content
        ]
      ]);

      if ($response->statusCode() !== 200) {
        $body = json_decode($response->body(), true);
        $errors[] = $body['errors'][0]['message'] ?? 'Template validation failed';
      }
    } catch (\Exception $e) {
      $errors[] = 'Template validation failed: ' . $e->getMessage();
    }

    return $errors;
  }

  public function duplicateTemplate(EmailTemplate $template): ?string
  {
    try {
      $response = $this->client->client->templates()->post([
        'name' => $template->name . ' (Copy)',
        'generation' => 'dynamic'
      ]);

      if ($response->statusCode() === 201) {
        $newTemplateId = json_decode($response->body())->id;

        // Copy the content to the new template
        $this->updateTemplateVersion($newTemplateId, $template);

        return $newTemplateId;
      }

      Log::error('SendGrid template duplication failed', [
        'status' => $response->statusCode(),
        'body' => $response->body()
      ]);

      return null;
    } catch (\Exception $e) {
      Log::error('SendGrid template duplication failed', [
        'error' => $e->getMessage()
      ]);
      return null;
    }
  }
}
