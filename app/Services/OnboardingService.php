<?php

namespace App\Services;

use App\Models\User;
use App\Models\OnboardingProgress;
use App\Enums\OnboardingStep;
use App\Exceptions\OnboardingException;

class OnboardingService
{
  private OnboardingStepRequestResolver $resolver;

  public function __construct(OnboardingStepRequestResolver $resolver)
  {
    $this->resolver = $resolver;
  }

  public function initializeProgress(User $user): OnboardingProgress
  {
    return OnboardingProgress::create([
      'user_id' => $user->id,
      'current_step' => OnboardingStep::WELCOME,
      'completed_steps' => [],
      'skipped_steps' => [],
      'form_data' => []
    ]);
  }

  public function validateStep(array $data, OnboardingStep $step): array
  {
    return $this->resolver->resolve($data, $step);
  }

  public function completeStep(OnboardingProgress $progress, array $data): void
  {
    $currentStep = $progress->current_step;
    $validatedData = $this->validateStep($data, $currentStep);

    $progress->updateFormData([
      $currentStep->value => $validatedData
    ]);

    $progress->markStepCompleted($currentStep);

    if ($currentStep === OnboardingStep::CUSTOMIZE) {
      $progress->complete();
    }
  }

  public function skipStep(OnboardingProgress $progress): void
  {
    $currentStep = $progress->current_step;

    if (!$currentStep->canSkip()) {
      throw new OnboardingException("Step {$currentStep->value} cannot be skipped");
    }

    $progress->markStepSkipped($currentStep);
  }

  public function isComplete(OnboardingProgress $progress): bool
  {
    $lastStep = OnboardingStep::CUSTOMIZE;
    return $progress->is_completed ||
      ($progress->isStepCompleted($lastStep) || $progress->isStepSkipped($lastStep));
  }
}
