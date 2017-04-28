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
 * Adds customized sort priority by Content Bundle.
 *
 * @SearchApiProcessor(
 *   id = "contentbundle",
 *   label = @Translation("Sort Priority by Content Bundle"),
 *   description = @Translation("Sort Priority by Content Bundle."),
 *   stages = {
 *     "add_properties" = 20,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class ContentBundle extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * Can only be enabled for an index that indexes the content bundle entity.
   *
   * {@inheritdoc}
   */
  public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      // TODO Not really sure about this logic.
      // Maybe peer review is required to check?
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
        'label' => $this->t('Sort Priority - Content Bundle'),
        // TODO Come up with better description.
        'description' => $this->t('Sort Priority - Content Bundle.'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['contentbundle_weight'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // TODO Figure out a better way to identify this field.
    $target_field_id = 'contentbundle_weight';

    // Get default weight.
    $weight = $this->configuration['weight'];

    // TODO We are only working with nodes for now.
    if ($item->getDatasource()->getEntityTypeId() == 'node') {
      $bundle_type = $item->getDatasource()->getItemBundle($item->getOriginalObject());
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($item->getFields(), NULL, $target_field_id);

      // Get the weight assigned to content type
      if ($this->configuration['sorttable'][$bundle_type]['weight']) {
        $weight = $this->configuration['sorttable'][$bundle_type]['weight'];
      }

      $fields[$target_field_id]->addValue($weight);
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
    $parent_name = 'processors[contentbundle][settings]';
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
        $this->t('Content Bundle'),
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

    // Get a list of available bundle_types defined on this index.
    $datasources = $this->index->getDatasources();
    foreach ($datasources as $datasource_id => $datasource) {
      // TODO Maybe this can be extended for non Node types?
      if ($datasource->getEntityTypeId() == 'node') {
        if ($bundles = $datasource->getBundles()) {
          // Loop over each bundle type and create a form row.
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
    drupal_set_message(t('<em>Sort Priority Content Bundle</em> Processor is enabled,
    you may add the <em>Sort Priority Content Bundle</em> field to your index to use this
    processor.'), 'status');
  }

}
