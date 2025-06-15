<?php

namespace App\Http\Controllers\Campaign;

use App\Enums\EmailTemplateCategory;
use App\Enums\EmailTemplateStatus;
use App\Enums\EmailTemplateType;
use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\MergeTag;
use App\Services\TemplateService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TemplateController extends Controller
{
  use AuthorizesRequests;

  public function __construct(
    protected TemplateService $templateService
  ) {}

  public function index(Request $request): \Inertia\Response
  {
    $query = EmailTemplate::query()
      ->where('team_id', auth()->user()->current_team_id)
      ->select(['id', 'uuid', 'name', 'description', 'subject', 'category', 'type', 'status']);

    if ($search = $request->input('search')) {
      $query->where('name', 'like', "%{$search}%")
        ->orWhere('subject', 'like', "%{$search}%")
        ->orWhere('description', 'like', "%{$search}%");
    }

    if ($category = $request->input('category')) {
      $query->where('category', $category);
    }

    if ($type = $request->input('type')) {
      $query->where('type', $type);
    }

    if ($status = $request->input('status')) {
      $query->where('status', $status);
    }

    // Sorting
    $sortField = $request->input('sort', 'category');
    $sortDirection = $request->input('direction', 'desc');
    $query->orderBy($sortField, $sortDirection);

    return Inertia::render('templates/Index', [
      'templates' => $query->paginate(10)->withQueryString(),
      'categories' => EmailTemplateCategory::getLabels(),
      'types' => EmailTemplateType::getLabels(),
      'statuses' => EmailTemplateStatus::getLabels(),
      'filters' => $request->only(['search', 'category', 'type', 'status', 'sort', 'direction']),
    ]);
  }

  public function create()
  {
    return Inertia::render('templates/Create');
  }

  public function store(Request $request)
  {
    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'description' => 'nullable|string|max:255',
      'subject' => 'required|string|max:255',
      'content' => 'required|string',
      'category' => 'required|string',
      'type' => 'required|string',
    ]);

    EmailTemplate::create(array_merge($validated, [
      'team_id' => auth()->user()->current_team_id,
      'user_id' => auth()->id(),
    ]));

    return redirect()->route('templates.index')->with('success', 'Template created successfully.');
  }

  public function edit(EmailTemplate $template): \Inertia\Response
  {
    // $this->authorize('update', $template);

    return Inertia::render('templates/Edit', [
      'template' => array_merge($template->toArray(), [
        'design' => $template->design ?? [],
        'variables' => $template->variables ?? [],
        'campaigns_count' => $template->campaigns()->count()
      ]),
      'categories' => collect(EmailTemplateCategory::cases())->map(fn($category) => [
        'value' => $category->value,
        'label' => str($category->value)->title()->toString()
      ])->values()->all(),
      'types' => collect(['drag-drop', 'html', 'text'])->map(fn($type) => [
        'value' => $type,
        'label' => str($type)->title()->toString()
      ])->values()->all(),
      'mergeTags' => MergeTag::query()
        ->select(['id', 'key', 'name', 'description', 'default', 'category'])
        ->orderBy('category')
        ->orderBy('name')
        ->get()
        ->groupBy('category')
        ->map(fn($tags) => $tags->map(fn($tag) => [
          'id' => $tag->id,
          'key' => $tag->key,
          'name' => $tag->name,
          'description' => $tag->description,
          'default' => $tag->default ?? ''
        ]))
        ->toArray()
    ]);
  }

  public function update(Request $request, EmailTemplate $template)
  {
    // $this->authorize('update', $template);

    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'description' => 'nullable|string|max:255',
      'subject' => 'required|string|max:255',
      'content' => 'required|string',
      'preview_text' => 'nullable|string|max:255',
      'category' => 'required|string',
      'type' => 'required|string',
      'design' => 'nullable|array',
      'variables' => 'nullable|array',
      'tags' => 'nullable|array'
    ]);

    try {
      $this->templateService->update($template, $validated);

      return redirect()
        ->route('app.templates.index')
        ->with('success', 'Template updated successfully.');
    } catch (\Exception $e) {
      report($e);

      return back()->withErrors([
        'error' => 'Could not update template. Please try again.'
      ]);
    }
  }

  public function destroy(EmailTemplate $template)
  {
    $template->delete();

    return redirect()->route('templates.index')->with('success', 'Template deleted successfully.');
  }
}
