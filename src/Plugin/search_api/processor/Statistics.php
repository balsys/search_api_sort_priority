<?php

namespace Drupal\search_api_sort_priority\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\user\RoleInterface;
use Drupal\Component\Utility\Html;
use Drupal\node\NodeInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\user\Entity\User;

/**
 * Adds customized sort priority by Statistics.
 *
 * @SearchApiProcessor(
 *   id = "statistics",
 *   label = @Translation("Sort Priority by Statistics"),
 *   description = @Translation("Sort Priority by Statistics."),
 *   stages = {
 *     "add_properties" = 20,
 *     "pre_index_save" = 0,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class Statistics extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  protected $targetFieldId = 'statistics_weight';

  /**
   * Can only be enabled for an index that indexes user related entity.
   *
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() == 'node') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        // TODO Come up with better label.
        'label' => $this->t('Sort Priority by Statistics'),
        // TODO Come up with better description.
        'description' => $this->t('Sort Priority by Statistics.'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
        // This will be a hidden field,
        // not something a user can add/remove manually.
        'hidden' => TRUE,
      ];
      $properties[$this->targetFieldId] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Get default weight.
    $weight = $this->configuration['weight'];

    // Only run for node and comment items.
    // TODO Extend for other entities.
    $entity_type_id = $item->getDatasource()->getEntityTypeId();
    if (!in_array($entity_type_id, $this->configuration['allowed_entity_types'])) {
      return;
    }

    $fields = $this->getFieldsHelper()
      ->filterForPropertyPath($item->getFields(), NULL, $this->targetFieldId);

    // TODO Extend for other entities.
    switch ($entity_type_id) {
      case 'node':


        // Set the weight on all the configured fields.
        foreach ($fields as $field) {
          $field->addValue($highest_role_weight['weight']);
        }
        break;
    }

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'weight' => 0,
      'allowed_entity_types' => [
        'node',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    // Automatically add field to index if processor is enabled.
    $field = $this->ensureField(NULL, $this->targetFieldId, 'integer');
    // Hide the field.
    $field->setHidden();
  }

}
