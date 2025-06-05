<?php

namespace App\Models;

use App\Traits\HasTeamScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Setting extends Model
{
  use HasTeamScope;

  protected $fillable = [
    'team_id',
    'settingable_type',
    'settingable_id',
    'key',
    'value',
    'metadata'
  ];

  protected $casts = [
    'value' => 'array',
    'metadata' => 'array'
  ];

  public function settingable(): MorphTo
  {
    return $this->morphTo();
  }

  public static function get(string $key, $default = null)
  {
    $setting = static::where('key', $key)
      ->forCurrentTeam()
      ->first();

    return $setting?->value ?? $default;
  }

  public static function set(string $key, $value, array $metadata = []): void
  {
    static::updateOrCreate(
      [
        'team_id' => auth()->user()->currentTeam->id,
        'key' => $key
      ],
      [
        'value' => $value,
        'metadata' => $metadata
      ]
    );
  }
}
