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

    $fields = $item->getFields();
    $target_field_id = 'sort_priority_node_type_weight_field';
    // TODO Add a check if Type field exists.
    $node_type = $fields['type']->getValues()[0];

    // TODO Maybe define the default weight somewhere else.
    $value = 100;

    switch ($node_type) {
      // TODO Add a loop that goes over the config factory settings.
      // And set the weight correct for every node type.
      case 'page':
        $value = '1';
        break;
    }
    if (empty($item->getField($target_field_id)->getValues())) {
      $item->getField($target_field_id)->addValue($value);
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

    $form['sorttable'] = array(
      '#type' => 'table',
      '#header' => [$this->t('Node Type'), $this->t('Weight')],
      '#tabledrag' => array(
        array(
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'sorttable-order-weight',
        ),
      ),
    );

    // Get a list of available node_types defined on this index.
    $datasources = $this->index->getDatasources();
    foreach ($datasources as $datasource_id => $datasource) {
      if ($datasource instanceof PluginFormInterface) { // TODO Not really sure what this does.
        if ($bundles = $datasource->getBundles()) {
          // Loop over each node type and create a form row.
          foreach ($bundles as $bundle_id => $bundle_name) {
            // Add form with weights
            // TableDrag: Mark the table row as draggable.
            $form['sorttable'][$bundle_id]['#attributes']['class'][] = 'draggable';

            // TableDrag: Sort the table row according to its existing/configured weight.
            $form['sorttable'][$bundle_id]['#weight'] = $this->configuration['weight'];

            // Some table columns containing raw markup.
            $form['sorttable'][$bundle_id]['label']['#plain_text'] = $bundle_name;

            // TableDrag: Weight column element.
            $form['sorttable'][$bundle_id]['weight'] = [
              '#type' => 'weight',
              '#title' => t('Weight for @title', ['@title' => $bundle_name]),
              '#title_display' => 'invisible',
              '#default_value' => $this->configuration['weight'],
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
    $weights = $form_state->getValues();
    //ksm($weights);
    foreach ($weights as $values) {
      //ksm($values['page']);
    }
    //$this->setConfiguration($form_state->getValues());
  }




}
