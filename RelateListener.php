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
      // ContentDeleted::class => 'deleted',
      // ContentSaved::class => 'saved'
    ];


    public function deleted(ContentDeleted $event)
    {
    }

    public function saved(ContentSaved $event)
    {
        // 1. Find "source" fields from fieldset

        $runner = new Runner($this->getConfig(), $event->data);

        foreach ($runner->getRelations() as $relation) {
            $relation->setDestinations($relation->sourceData);
        }

        // 2. For each source field, grab data from event
        // 3. Get destination
        //    - Content Type
        //    - Data
        //    - Fieldtype & Field
        // 4. Loop through destination content and check field values


        $fields = $fieldset->fields();

        foreach ($fields as $field) {
            $destinations = $config->getDestinations($fieldset, $field);
            foreach ($destinations as $destination) {
              $this->handle($field);
            }
        }

        dd($config);
        $fieldKey = array_keys($config)[0];

        $related = $config[$fieldKey];

        // $field = $fields[];
    }
}

class Relation
{
  public function __construct($field, $data, $destination)
  {
      $this->field = $field;
      $this->data = $data;
      $this->destination = $destination;
  }

  public function setDestinations()
  {
      foreach ($this->contentList() as $content) {
          if ($content->fieldset()->name() != $this->destination['fieldset']) {
            continue;
          }

          $value = $data->get($field->name());

          // IF we have an array of values, then we want to update or remove if contained.
          // If 

          $content->set(
              $this->destination['field'],

          )
      }
  }

  public function sourceData()
  {
      $data->get($this->field->name());
  }

  private function contentList()
  {
      switch ($this->field['type']) {
        case 'collection':
          $nested = array_map(function($collection) {
            return Entry::whereCollection($collection);
          }, $this->field['collection']);

          return array_merge(...$nested);
        case 'pages':
          return Page::all();
      }
  }
}

class Runner
{
    public function __construct($config = [])
    {
        $flipped = array_flip($config);

        $total = $flipped + $config;

        foreach ($total as $key => $value) {
            $source = explode($key, "-");
            $destination = explode($value, "-");

            array_push($this->relations, [
              "source" => [
                "fieldset" => $source[0],
                "field" => $source[1]
              ],
              "destination" => [
                "fieldset" => $destination[0],
                "field" => $destination[1]
              ]
            ]);
        }
    }

    public function getRelations()
    {
      // code...
    }

    public function getDestinations($fieldset, $field)
    {
        return array_filter(
          $this->relations,
          function ($relation) use ($fieldset, $field) {
              return $relation["source"]["fieldset"] == $fieldset && $relation["source"]["field"] == $field;
          }
        );
    }
}
