<?php

namespace Statamic\Addons\Relate;

use Statamic\Extend\Listener;
use Statamic\Events\Data\ContentDeleted;
use Statamic\Events\Data\ContentSaved;

class RelateListener extends Listener
{
    /**
     * The events to be listened for, and the methods to call.
     *
     * @var array
     */
    public $events = [
      ContentDeleted::class => 'deleted',
      ContentSaved::class => 'saved'
    ];


    public function deleted(ContentDeleted $event)
    {
      // code...
    }

    public function saved(ContentSaved $event)
    {
      // code...
    }
}
