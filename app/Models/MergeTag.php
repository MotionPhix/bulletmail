<?php

namespace App\Models;

use Sushi\Sushi;
use Illuminate\Database\Eloquent\Model;

class MergeTag extends Model
{
  use Sushi;

  protected $rows = [
    [
      'key' => 'subscriber.first_name',
      'name' => 'First Name',
      'description' => 'Subscriber\'s first name',
      'default' => 'Friend',
      'category' => 'subscriber',
      'required' => true,
    ],
    [
      'key' => 'subscriber.last_name',
      'name' => 'Last Name',
      'description' => 'Subscriber\'s last name',
      'default' => '',
      'category' => 'subscriber',
      'required' => false,
    ],
    [
      'key' => 'subscriber.email',
      'name' => 'Email',
      'description' => 'Subscriber\'s email address',
      'default' => '',
      'category' => 'subscriber',
      'required' => true,
    ],
    [
      'key' => 'subscriber.unsubscribe_link',
      'name' => 'Unsubscribe Link',
      'description' => 'Unsubscribe link for the subscriber',
      'default' => '',
      'category' => 'subscriber',
      'required' => true,
    ],
    [
      'key' => 'campaign.name',
      'name' => 'Campaign Name',
      'description' => 'Name of the campaign',
      'default' => '',
      'category' => 'campaign',
      'required' => false,
    ],
    [
      'key' => 'campaign.subject',
      'name' => 'Subject',
      'description' => 'Campaign subject line',
      'default' => '',
      'category' => 'campaign',
      'required' => false,
    ],
    [
      'key' => 'organization.name',
      'name' => 'Organization Name',
      'description' => 'Your organization name',
      'default' => '',
      'category' => 'organization',
      'required' => false,
    ],
    [
      'key' => 'sender.name',
      'name' => 'Sender Name',
      'description' => 'From name for the campaign',
      'default' => '',
      'category' => 'sender',
      'required' => false,
    ],
    [
      'key' => 'sender.email',
      'name' => 'Sender Email',
      'description' => 'From email for the campaign',
      'default' => '',
      'category' => 'sender',
      'required' => false,
    ],
  ];
}
