<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
  use HasFactory, HasUuid, SoftDeletes;

  protected $fillable = [
    'name',
    'slug',
    'description',
    'price',
    'currency',
    'trial_days',
    'is_active',
    'is_featured',
    'sort_order',
    'features',
    'metadata'
  ];

  protected $casts = [
    'price' => 'integer',
    'trial_days' => 'integer',
    'is_active' => 'boolean',
    'is_featured' => 'boolean',
    'features' => 'array',
    'metadata' => 'array'
  ];

  public function subscriptions(): HasMany
  {
    return $this->hasMany(Subscription::class);
  }

  public function isFreePlan(): bool
  {
    return $this->price === 0;
  }

  public function getFeature(string $key, $default = null)
  {
    return data_get($this->features, $key, $default);
  }

  public function hasFeature(string $feature): bool
  {
    return (bool) $this->getFeature($feature);
  }

  public function getEmailLimit(): int
  {
    $limit = $this->getFeature('email_limit', 0);
    return is_numeric($limit) ? (int) $limit : PHP_INT_MAX;
  }

  public function getCampaignLimit(): int
  {
    $limit = $this->getFeature('campaign_limit', 0);
    return is_numeric($limit) ? (int) $limit : PHP_INT_MAX;
  }

  public function getSubscriberLimit(): int
  {
    $limit = $this->getFeature('subscriber_limit', 0);
    return is_numeric($limit) ? (int) $limit : PHP_INT_MAX;
  }
}
