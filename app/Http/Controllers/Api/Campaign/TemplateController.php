<?php

namespace App\Http\Controllers\Api\Campaign;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
  public function show(EmailTemplate $template)
  {
    return response()->json([
      'template' => $template->only(['id', 'uuid', 'name', 'subject', 'content', 'design']),
    ]);
  }
}
