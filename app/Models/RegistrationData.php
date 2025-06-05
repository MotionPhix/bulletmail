<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationData extends Model
{
  protected $fillable = [
    'user_id',
    'step',
    'data',
    'is_completed',
    'completed_at'
  ];

  protected $casts = [
    'data' => 'array',
    'is_completed' => 'boolean',
    'completed_at' => 'datetime'
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function markAsCompleted(): void
  {
    $this->update([
      'is_completed' => true,
      'completed_at' => now()
    ]);
  }

  public function setStepData(array $data): void
  {
    $this->update([
      'data' => array_merge($this->data ?? [], $data)
    ]);
  }
}
