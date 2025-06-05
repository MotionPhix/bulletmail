<?php

namespace App\Services;

use App\Http\Requests\Onboarding\BaseStepRequest;
use App\Http\Requests\Onboarding\Step2Request;
use App\Http\Requests\Onboarding\Step3Request;
use App\Http\Requests\Onboarding\Step4Request;
use App\Http\Requests\Onboarding\Step5Request;
use App\Http\Requests\Onboarding\Step6Request;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OnboardingStepRequestResolver
{
  private const STEP_REQUESTS = [
    2 => Step2Request::class,
    3 => Step3Request::class,
    4 => Step4Request::class,
    5 => Step5Request::class,
    6 => Step6Request::class,
  ];

  public function resolve(Request $request): array
  {
    $step = $request->input('step');
    $requestClass = self::STEP_REQUESTS[$step] ?? null;

    if (!$requestClass) {
      throw new \InvalidArgumentException("Invalid step number: {$step}");
    }

    /** @var BaseStepRequest $formRequest */
    $formRequest = new $requestClass;

    // Get validation rules from the form request
    $rules = $formRequest->rules(); // Make sure this calls stepRules() method
    $messages = method_exists($formRequest, 'messages') ? $formRequest->messages() : [];

    // Debugging: Output validation rules and request data
    \Log::info('Validation rules: ', $rules);
    \Log::info('Request data: ', $request->all());

    // Create validator instance
    $validator = Validator::make($request->all(), $rules, $messages);

    if ($validator->fails()) {

      \Log::error('Validation errors: ', $validator->errors()->toArray());
      // Return error messages in a custom error bag if necessary
      throw ValidationException::withMessages($validator->errors()->toArray());
    }

    return $validator->validated();
  }
}
