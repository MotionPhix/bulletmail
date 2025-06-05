<?php

namespace App\Http\Controllers;

use App\Exceptions\OnboardingException;
use App\Http\Requests\Onboarding\Step6Request;
use App\Services\OnboardingService;
use App\Services\OnboardingStepRequestResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class OnboardingController extends Controller
{
  public function __construct(
    protected OnboardingService $onboardingService,
    protected OnboardingStepRequestResolver $stepResolver
  ) {}

  public function index(): Response
  {
    try {
      $progress = $this->onboardingService->getOrCreateProgress(auth()->user());

      return Inertia::render('OnBoarding/Index', [
        'progress' =>  [
          'current_step' => $progress->current_step,
          'completed_steps' => $progress->completed_steps ?? [],
          'skipped_steps' => $progress->skipped_steps ?? [],
          'form_data' => $progress->form_data ?? [],
          'is_completed' => $progress->is_completed,
        ],
        'required_steps' => OnboardingService::REQUIRED_STEPS,
        'userSettings' => auth()->user()->settings,
      ]);
    } catch (Throwable $e) {
      Log::error('Failed to load onboarding page', [
        'error' => $e->getMessage(),
        'user_id' => auth()->id(),
      ]);

      return Inertia::render('Error', [
        'status' => 500,
        'message' => 'Failed to load onboarding. Please try again.',
      ]);
    }
  }

  public function skip()
  {
    try {
      DB::beginTransaction();

      $user = auth()->user();
      $progress = $this->onboardingService->getOrCreateProgress($user);

      // Mark all steps as skipped (including required ones)
      $allSteps = range(1, 6);
      foreach ($allSteps as $step) {
        if (!in_array($step, $progress->skipped_steps ?? [])) {
          $progress->skipped_steps = array_merge($progress->skipped_steps ?? [], [$step]);
        }
      }

      // Mark onboarding as completed but flag settings as incomplete
      $progress->is_completed = true;
      $progress->completed_at = now();
      $progress->settings_pending = true; // Add this flag to track pending settings
      $progress->save();

      // Update user settings to indicate required setup is pending
      $user->settings()->update([
        'account_setup_completed' => false
      ]);

      DB::commit();

      return redirect()->route('settings.account')->with('warning',
        'Please configure your account settings before sending campaigns.');
    } catch (Throwable $e) {
      DB::rollBack();
      Log::error('Failed to skip onboarding', [
        'error' => $e->getMessage(),
        'user_id' => auth()->id(),
      ]);

      return back()->withErrors([
        'message' => 'Failed to skip onboarding. Please try again.',
      ]);
    }
  }

  public function skipCurrent(Request $request)
  {
    try {
      DB::beginTransaction();

      $progress = $this->onboardingService->skipCurrentStep($request->user());

      DB::commit();

      return back()->with([
        'message' => 'Step skipped successfully',
        'progress' => $progress->fresh(),
      ]);
    } catch (OnboardingException $e) {
      DB::rollBack();

      return back()->withErrors(['step' => $e->getMessage()], 'default');
    } catch (Throwable $e) {
      DB::rollBack();
      Log::error('Failed to skip current onboarding step', [
        'error' => $e->getMessage(),
        'user_id' => auth()->id(),
      ]);

      return back()->withErrors([
        'message' => 'Failed to skip step. Please try again.',
      ]);
    }
  }

  public function updateStep(Request $request)
  {
    // Resolve and validate the appropriate request for this step
    $validated = $this->stepResolver->resolve($request);

    try {
      DB::beginTransaction();

      $progress = $this->onboardingService->updateStep(
        $request->user(),
        $validated['step'],
        $validated['data']
      );

      DB::commit();

      return back()->with([
        'message' => 'Step updated successfully',
        'progress' => $progress->fresh(),
      ]);
    } catch (OnboardingException $e) {
      DB::rollBack();

      return back()->withErrors([
        'step' => $e->getMessage(),
        'errors' => $e->getErrors(),
      ]);
    } catch (Throwable $e) {
      DB::rollBack();
      Log::error('Failed to update onboarding step', [
        'error' => $e->getMessage(),
        'step' => $request->step,
        'user_id' => auth()->id(),
      ]);

      return back()->withErrors([
        'message' => 'Failed to update step. Please try again.',
      ]);
    }
  }

  public function completeOnboarding(): JsonResponse
  {
    try {
      DB::beginTransaction();

      $this->onboardingService->completeOnboarding(auth()->user());

      DB::commit();

      return response()->json([
        'message' => 'Onboarding completed successfully',
      ]);
    } catch (OnboardingException $e) {
      DB::rollBack();

      return response()->json([
        'message' => $e->getMessage(),
        'errors' => $e->getErrors(),
      ], 422);
    } catch (Throwable $e) {
      DB::rollBack();
      Log::error('Failed to complete onboarding', [
        'error' => $e->getMessage(),
        'user_id' => auth()->id(),
      ]);

      return response()->json([
        'message' => 'Failed to complete onboarding. Please try again.',
      ], 500);
    }
  }
}
