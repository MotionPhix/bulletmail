<?php

namespace App\Traits;

use phpDocumentor\Reflection\Types\Self_;

trait HasRegistrationSteps
{
  /**
   * Registration steps configuration.
   */
  public static function getRegistrationSteps(): array
  {
    return [
      self::REGISTRATION_STEP_ACCOUNT => [
        'title' => 'Account Setup',
        'fields' => ['first_name', 'last_name', 'email', 'password'],
        'required' => true,
      ],
      self::REGISTRATION_STEP_ORGANIZATION => [
        'title' => 'Company Information',
        'fields' => ['company_name', 'industry', 'company_size', 'website'],
        'required' => true,
      ],
      self::REGISTRATION_STEP_TEAM => [
        'title' => 'Team Setup',
        'fields' => ['invited_team_members'],
        'required' => false,
      ],
      self::REGISTRATION_STEP_VERIFICATION => [
        'title' => 'Verification',
        'fields' => ['email_verified'],
        'required' => true,
      ],
    ];
  }

  /**
   * Validate if user can proceed to next step.
   */
  public function canProceedToStep(int $step): bool
  {
    $steps = self::getRegistrationSteps();

    // Check if step exists
    if (!isset($steps[$step])) {
      return false;
    }

    // Check if previous steps are completed
    for ($i = 1; $i < $step; $i++) {
      if ($steps[$i]['required'] && !$this->hasCompletedStep($i)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Get validation rules for a step.
   */
  public function getStepValidationRules(int $step): array
  {
    return match($step) {
      1 => [
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:8|confirmed',
      ],
      2 => [
        'company_name' => 'required|string|max:255',
        'industry' => 'required|string',
        'company_size' => 'required|string',
        'website' => 'nullable|url',
      ],
      3 => [
        'invited_team_members' => 'nullable|array',
        'invited_team_members.*.email' => 'required|email',
        'invited_team_members.*.role' => 'required|string|in:admin,editor,member',
      ],
      4 => [
        'email_verified' => 'required|boolean|accepted',
      ],
      default => [],
    };
  }
}
