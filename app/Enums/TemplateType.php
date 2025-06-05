<?php

namespace App\Enums;

enum TemplateType: string
{
  case DRAG_DROP = 'drag-drop';
  case HTML = 'html';
  case MARKDOWN = 'markdown';
  case PLAIN_TEXT = 'plain-text';
}
