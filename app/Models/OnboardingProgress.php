<?php

namespace App\Models;

use App\Enums\OnboardingStep;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingProgress extends Model
{
  protected $fillable = [
    'user_id',
    'completed_steps',
    'skipped_steps',
    'form_data',
    'is_completed',
    'current_step',
    'completed_at'
  ];

  protected $casts = [
    'completed_steps' => 'array',
    'skipped_steps' => 'array',
    'form_data' => 'array',
    'is_completed' => 'boolean',
    'completed_at' => 'datetime',
    'current_step' => OnboardingStep::class
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function markStepCompleted(OnboardingStep $step): void
  {
    $completedSteps = $this->completed_steps ?? [];
    $completedSteps[] = $step->value;
    $this->completed_steps = array_unique($completedSteps);

    if ($this->current_step->value === $step->value) {
      $this->current_step = OnboardingStep::from($step->value + 1);
    }

    $this->save();
  }

  public function markStepSkipped(OnboardingStep $step): void
  {
    if (!$step->canSkip()) {
      throw new \InvalidArgumentException("Step {$step->value} cannot be skipped");
    }

    $skippedSteps = $this->skipped_steps ?? [];
    $skippedSteps[] = $step->value;
    $this->skipped_steps = array_unique($skippedSteps);

    if ($this->current_step->value === $step->value) {
      $this->current_step = OnboardingStep::from($step->value + 1);
    }

    $this->save();
  }

  public function isStepCompleted(OnboardingStep $step): bool
  {
    return in_array($step->value, $this->completed_steps ?? []);
  }

  public function isStepSkipped(OnboardingStep $step): bool
  {
    return in_array($step->value, $this->skipped_steps ?? []);
  }

  public function updateFormData(array $data): void
  {
    $this->form_data = array_merge($this->form_data ?? [], $data);
    $this->save();
  }

  public function complete(): void
  {
    $this->is_completed = true;
    $this->completed_at = now();
    $this->save();
  }
}
