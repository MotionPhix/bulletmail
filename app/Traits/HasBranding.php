<?php

namespace App\Traits;

use App\Models\Branding;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

trait HasBranding
{
  public function branding(): HasOne
  {
    return $this->hasOne(Branding::class);
  }

  public function initializeBranding(): void
  {
    $this->branding()->create([
      'company_name' => $this->company_name ?? '',
      'primary_color' => '#4f46e5',
      'accent_color' => '#10b981',
    ]);
  }

  public function updateBranding(array $data): bool
  {
    if (!$this->branding) {
      $this->initializeBranding();
    }

    if (isset($data['logo']) && $data['logo']) {
      if ($this->branding->logo_path) {
        Storage::delete($this->branding->logo_path);
      }
      $data['logo_path'] = $data['logo']->store('branding/logos', 'public');
      unset($data['logo']);
    }

    return $this->branding->update($data);
  }

  public function getEmailTemplate(): array
  {
    if (!$this->branding) {
      return [
        'header' => '',
        'footer' => '',
        'styles' => ''
      ];
    }

    return [
      'header' => $this->branding->email_header,
      'footer' => $this->branding->email_footer,
      'styles' => $this->getCustomStyles()
    ];
  }

  protected function getCustomStyles(): string
  {
    if (!$this->branding) {
      return '';
    }

    return "
      :root {
        --primary-color: {$this->branding->primary_color};
        --accent-color: {$this->branding->accent_color};
      }
      {$this->branding->custom_css}
    ";
  }
}
