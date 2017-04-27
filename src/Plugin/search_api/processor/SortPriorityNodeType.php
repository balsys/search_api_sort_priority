<?php

namespace Drupal\search_api_sort_priority\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\Utility\Utility;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\PluginFormTrait;

/**
 * Adds customized sort priority by Node Type.
 *
 * @SearchApiProcessor(
 *   id = "sortprioritynodetype",
 *   label = @Translation("Sort Priority by Node Type"),
 *   description = @Translation("Sort Priority by Node Type."),
 *   stages = {
 *     "add_properties" = 20,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class SortPriorityNodeType extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * Can only be enabled for an index that indexes the node entity.
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
        'label' => $this->t('Sort Priority - Node Type weight field'),
        // TODO Come up with better description.
        'description' => $this->t('Sort Priority - Node Type weight field.'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['sort_priority_node_type_weight_field'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $target_field_id = 'sort_priority_node_type_weight_field';
    $weight = $this->configuration['weight'];

    $fields = $item->getFields();
    // TODO Add a check if Node Type field exists.
    $node_type = $fields['type']->getValues()[0];

    if ($this->configuration['sorttable'][$node_type]['weight']) {
      $weight = $this->configuration['sorttable'][$node_type]['weight'];
    }

    if (empty($item->getField($target_field_id)->getValues())) {
      $item->getField($target_field_id)->addValue($weight);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'weight' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $parent_name = 'processors[sortprioritynodetype][settings]';
    if (!empty($form['#parents'])) {
      $parents = $form['#parents'];
      $parent_name = $root = array_shift($parents);
      if ($parents) {
        $parent_name = $root . '[' . implode('][', $parents) . ']';
      }
    }

    $form['sorttable'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Node Type'),
        $this->t('Weight')
      ],
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'sorttable-order-weight',
        ],
      ],
    ];

    // Get a list of available node_types defined on this index.
    $datasources = $this->index->getDatasources();
    foreach ($datasources as $datasource_id => $datasource) {
      if ($datasource instanceof PluginFormInterface) { // TODO Not really sure what this does.
        if ($bundles = $datasource->getBundles()) {
          // Loop over each node type and create a form row.
          foreach ($bundles as $bundle_id => $bundle_name) {
            $weight = $this->configuration['weight'];
            if ($this->configuration['sorttable'][$bundle_id]['weight']) {
              $weight = $this->configuration['sorttable'][$bundle_id]['weight'];
            }

            // Add form with weights
            // Mark the table row as draggable.
            $form['sorttable'][$bundle_id]['#attributes']['class'][] = 'draggable';

            // Sort the table row according to its existing/configured weight.
            // TODO Check why the rows are not sorted by weight.
            $form['sorttable'][$bundle_id]['#weight'] = $weight;

            // Table columns containing raw markup.
            $form['sorttable'][$bundle_id]['label']['#plain_text'] = $bundle_name;

            // Weight column element.
            $form['sorttable'][$bundle_id]['weight'] = [
              '#type' => 'weight',
              '#title' => t('Weight for @title', ['@title' => $bundle_name]),
              '#title_display' => 'invisible',
              '#default_value' => $weight,
              // Classify the weight element for #tabledrag.
              '#attributes' => ['class' => ['sorttable-order-weight']],
            ];
          }
        }
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

}
