<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Branding extends Model
{
  protected $fillable = [
    'user_id',
    'company_name',
    'logo_path',
    'primary_color',
    'accent_color',
    'email_header',
    'email_footer',
    'custom_css'
  ];

  protected $casts = [
    'primary_color' => 'string',
    'accent_color' => 'string'
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
