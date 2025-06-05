<?php

namespace App\Http\Controllers;

use App\Models\EmailTemplate;
use App\Services\SendGrid\SendGridService;
use App\Services\EmailPersonalizationService;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Inertia\Inertia;

class EmailTemplateController extends Controller
{
  public function __construct(
    protected SendGridService $sendGrid,
    protected EmailPersonalizationService $personalization
  ) {}

  public function index()
  {
    if (!auth()->user()->can('viewAny', EmailTemplate::class)) {
      throw new AuthorizationException('Not authorized to view templates.');
    }

    $templates = auth()->user()->currentTeam
      ->templates()
      ->latest()
      ->with('user')
      ->paginate(10);

    return Inertia::render('Templates/Index', [
      'templates' => $templates,
      'can' => [
        'create' => auth()->user()->can('create', EmailTemplate::class),
        'update' => auth()->user()->can('update', EmailTemplate::class),
        'delete' => auth()->user()->can('delete', EmailTemplate::class)
      ]
    ]);
  }

  public function create()
  {
    return Inertia::render('Templates/Create', [
      'variables' => $this->personalization->getAvailableVariables(),
      'categories' => EmailTemplate::CATEGORIES,
      'types' => EmailTemplate::TYPES
    ]);
  }

  public function store(Request $request)
  {
    $this->authorize('create', EmailTemplate::class);

    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'description' => 'nullable|string',
      'subject' => 'required|string|max:255',
      'content' => 'required|string',
      'preview_text' => 'nullable|string',
      'category' => 'required|string|in:' . implode(',', EmailTemplate::CATEGORIES),
      'type' => 'required|string|in:' . implode(',', EmailTemplate::TYPES),
      'variables' => 'nullable|array',
      'design' => 'nullable|array',
      'tags' => 'nullable|array'
    ]);

    $template = auth()->user()->currentTeam->templates()->create(array_merge(
      $validated,
      ['user_id' => auth()->id()]
    ));

    // Sync with SendGrid if it's an HTML template
    if ($template->type === 'html') {
      $sendgridId = $this->sendGrid->syncTemplate($template);
      if ($sendgridId) {
        $template->update(['sendgrid_template_id' => $sendgridId]);
      }
    }

    return redirect()->route('templates.index')
      ->with('success', 'Template created successfully.');
  }

  public function edit(EmailTemplate $template)
  {
    $this->authorize('update', $template);

    return Inertia::render('Templates/Edit', [
      'template' => $template,
      'variables' => $this->personalization->getAvailableVariables(),
      'categories' => EmailTemplate::CATEGORIES,
      'types' => EmailTemplate::TYPES
    ]);
  }

  public function update(Request $request, EmailTemplate $template)
  {
    $this->authorize('update', $template);

    $validated = $request->validate([
      'name' => 'required|string|max:255',
      'description' => 'nullable|string',
      'subject' => 'required|string|max:255',
      'content' => 'required|string',
      'preview_text' => 'nullable|string',
      'category' => 'required|string|in:' . implode(',', EmailTemplate::CATEGORIES),
      'type' => 'required|string|in:' . implode(',', EmailTemplate::TYPES),
      'variables' => 'nullable|array',
      'design' => 'nullable|array',
      'tags' => 'nullable|array'
    ]);

    $template->update($validated);

    // Sync with SendGrid if it's an HTML template
    if ($template->type === 'html' && $template->isDirty('content')) {
      $this->sendGrid->updateTemplateVersion($template);
    }

    return redirect()->route('templates.index')
      ->with('success', 'Template updated successfully.');
  }

  public function preview(EmailTemplate $template)
  {
    $this->authorize('view', $template);

    $previewData = [
      'first_name' => 'John',
      'last_name' => 'Doe',
      'email' => 'john@example.com',
      'company' => 'ACME Inc.'
    ];

    $renderedContent = $this->personalization
      ->setCustomVariables($template->variables ?? [])
      ->parseTemplate($template->content, $previewData);

    return response()->json([
      'content' => $renderedContent,
      'subject' => $this->personalization->parseTemplate($template->subject, $previewData)
    ]);
  }

  public function destroy(EmailTemplate $template)
  {
    $this->authorize('delete', $template);

    $template->delete();

    return redirect()->route('templates.index')
      ->with('success', 'Template deleted successfully.');
  }
}
