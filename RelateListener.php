<?php

namespace Statamic\Addons\Relate;

use Statamic\Extend\Listener;
use Statamic\Events\Data\ContentDeleted;
use Statamic\Events\Data\ContentSaved;
use Statamic\API\Entry;
use Statamic\API\Fieldset;
use Statamic\API\Page;

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
    }

    public function saved(ContentSaved $event)
    {
        $config = new Config($this->getConfig());

        $relations = $config->getRelations($event->data);
        foreach ($relations as $relation) {
            $relation->setTargets();
        }
    }
}

class Relation
{
  public function __construct($sourceID, $sourceRelations, $sourceField, $targetConfig)
  {

      $this->sourceID = $sourceID;
      $this->sourceRelations = is_array($sourceRelations) ? $sourceRelations : [$sourceRelations];
      $this->sourceField = $sourceField;

      $this->targetConfig = $targetConfig;
      $targetField = Fieldset::get($targetConfig['fieldset'])
                             ->fields()[$targetConfig['field']];

      $this->targetField = $targetField;
  }

  public function setTargets()
  {
      $foreignKey = $this->sourceID;
      $relations = $this->sourceRelations;

      $list = $this->contentList();

      foreach ($list as $item) {

          $original = $item;
          if ($item->fieldset()->name() != $this->targetConfig['fieldset']) {
            continue;
          }

          $targetFieldValueOriginal = $item->get($this->targetConfig['field'], []);
          $targetFieldValueOriginal = is_array($targetFieldValueOriginal) ? $targetFieldValueOriginal : [$targetFieldValueOriginal];

          $targetFieldValue = $targetFieldValueOriginal;
          $limit = @$this->targetField['max_items'] ?? 10000;


          if (in_array($item->id(), $relations)) {
            // Ensure it's related
            if ($limit == 1) {
              $targetFieldValue = $foreignKey;
            } else {
              $targetFieldValue = array_values(array_unique(array_merge($targetFieldValue, [$foreignKey])));
              $targetFieldValue = array_slice($targetFieldValue, 0, intval($limit));
            }
          } else {
            // Remove it
            $targetFieldValue = array_diff($targetFieldValue, [$foreignKey]);
          }

          if (empty($targetFieldValue)) {
            $item->remove($this->targetConfig['field']);
          } else {
            $item->set(
                $this->targetConfig['field'],
                $targetFieldValue
            );
          }

          if ($targetFieldValueOriginal != $targetFieldValue) {
            $item->save();
          }
      }
  }

  private function contentList()
  {
      switch ($this->sourceField['type']) {
        case 'collection':
          return array_reduce(
            $this->sourceField['collection'],
            function($agg, $collection) {
              return $agg->merge(Entry::whereCollection($collection));
            },
            new \Illuminate\Support\Collection()
          );

        case 'pages':
          return Page::all();
      }
  }
}

class Config
{
    public function __construct($config = [])
    {
        $this->relations = [];

        // Build two-way bindings from config.

        $flipped = array_flip($config);

        $total = $config + $flipped;

        foreach ($total as $key => $value) {
            $source = explode("-", $key);
            $target = explode("-", $value);

            array_push($this->relations, [
              "source" => [
                "fieldset" => $source[0],
                "field" => $source[1]
              ],
              "target" => [
                "fieldset" => $target[0],
                "field" => $target[1]
              ]
            ]);
        }
    }

    public function getRelations($sourceData)
    {
        $agg = [];
        foreach ($this->relations as $config) {
          $isFieldset = $sourceData->fieldset()->name() == $config['source']['fieldset'];
          $relations = $sourceData->get($config['source']['field'], false);

          if ($isFieldset && $relations) {
            $sourceField = $sourceData->fieldset()->fields()[$config['source']['field']];
            $relation = new Relation($sourceData->id(), $relations, $sourceField, $config['target']);
            array_push($agg, $relation);
          }
        }
        return $agg;
    }

    public function getTargets($fieldset, $field)
    {
        return array_filter(
          $this->relations,
          function ($relation) use ($fieldset, $field) {
              return $relation["source"]["fieldset"] == $fieldset && $relation["source"]["field"] == $field;
          }
        );
    }
}
