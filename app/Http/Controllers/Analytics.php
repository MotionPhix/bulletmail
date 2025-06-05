<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class Analytics extends Controller
{
  public function __construct(
    private readonly AnalyticsService $analyticsService
  ) {}

  public function __invoke(Request $request)
  {
    $this->analyticsService->setUser(Auth::user());

    try {
      // Validate with more specific rules
      $validated = $request->validate([
        'start_date' => 'nullable|date|before:tomorrow',
        'end_date' => 'nullable|date|after_or_equal:start_date|before:tomorrow',
        'period' => 'nullable|string|in:7d,30d,90d,1y,custom',
        'compare' => 'nullable|boolean' // Add comparison option
      ]);

      // Handle period selection with validation
      $dates = $this->getDateRange($validated['period'] ?? '30d', [
        'start_date' => $validated['start_date'] ?? null,
        'end_date' => $validated['end_date'] ?? null,
      ]);

      // Validate date range isn't too large
      if ($dates['start']->diffInDays($dates['end']) > 366) {
        throw ValidationException::withMessages([
          'period' => 'Date range cannot exceed 1 year'
        ]);
      }

      // Get analytics data with comparison if requested
      $dashboardData = $this->analyticsService->getDashboardStats(
        $dates['start']->toDateTimeString(),
        $dates['end']->toDateTimeString(),
        $validated['compare'] ?? false
      );

      return Inertia::render('Analytics/Index', [
        ...$dashboardData,
        'filters' => [
          'period' => $validated['period'] ?? '30d',
          'start_date' => $dates['start']->toDateString(),
          'end_date' => $dates['end']->toDateString(),
          'compare' => $validated['compare'] ?? false
        ],
        'periods' => [
          ['value' => '7d', 'label' => 'Last 7 days'],
          ['value' => '30d', 'label' => 'Last 30 days'],
          ['value' => '90d', 'label' => 'Last 90 days'],
          ['value' => '1y', 'label' => 'Last year'],
          ['value' => 'custom', 'label' => 'Custom range']
        ]
      ]);
    } catch (ValidationException $e) {
      return Inertia::render('Analytics/Index', [
        'error' => $e->getMessage(),
        'errors' => $e->errors()
      ])->toResponse($request)
        ->setStatusCode(Response::HTTP_UNPROCESSABLE_ENTITY);
    } catch (\Exception $e) {
      report($e); // Log the error

      return Inertia::render('Analytics/Index', [
        'error' => config('app.debug')
          ? $e->getMessage()
          : 'An error occurred while fetching dashboard data.',
      ])->toResponse($request)
        ->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
    }
  }

  private function getDateRange(string $period, array $customDates): array
  {
    $end = Carbon::now()->endOfDay();

    // Handle timezone consistently
    $start = match ($period) {
      '7d' => Carbon::now()->subDays(7)->startOfDay(),
      '30d' => Carbon::now()->subDays(30)->startOfDay(),
      '90d' => Carbon::now()->subDays(90)->startOfDay(),
      '1y' => Carbon::now()->subYear()->startOfDay(),
      'custom' => isset($customDates['start_date'])
        ? Carbon::parse($customDates['start_date'])->startOfDay()
        : Carbon::now()->subDays(30)->startOfDay(),
      default => Carbon::now()->subDays(30)->startOfDay(),
    };

    if ($period === 'custom' && !empty($customDates['end_date'])) {
      $end = Carbon::parse($customDates['end_date'])->endOfDay();
    }

    return [
      'start' => $start,
      'end' => $end,
    ];
  }
}
