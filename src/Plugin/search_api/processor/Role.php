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

use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;
use Drupal\Component\Utility\Html;

/**
 * Adds customized sort priority by Role.
 *
 * @SearchApiProcessor(
 *   id = "role",
 *   label = @Translation("Sort Priority by Role"),
 *   description = @Translation("Sort Priority by Role."),
 *   stages = {
 *     "add_properties" = 20,
 *     "pre_index_save" = 0,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class Role extends ProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  protected $target_field_id = 'role_weight';

  /**
   * Can only be enabled for an index that indexes user related entity.
   *
   * {@inheritdoc}
   */
  /*public static function supportsIndex(IndexInterface $index) {
    foreach ($index->getDatasources() as $datasource) {
      if ($datasource->getEntityTypeId() == 'node') {
        return TRUE;
      }
    }
    return FALSE;
  }*/

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        // TODO Come up with better label.
        'label' => $this->t('Sort Priority by Role'),
        // TODO Come up with better description.
        'description' => $this->t('Sort Priority by Role.'),
        'type' => 'integer',
        'processor_id' => $this->getPluginId(),
        // This will be a hidden field,
        // not something a user can add/remove manually.
        'hidden' => TRUE,
      ];
      $properties[$this->target_field_id] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    // Get default weight.
    $weight = $this->configuration['weight'];

    // TODO We are only working with nodes for now.
    /*if ($item->getDatasource()->getEntityTypeId() == 'node') {
      $role_id = $item->getDatasource()->getItemBundle($item->getOriginalObject());
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($item->getFields(), NULL, $this->target_field_id);

      // Get the weight assigned to role
      if ($this->configuration['sorttable'][$role_id]['weight']) {
        $weight = $this->configuration['sorttable'][$role_id]['weight'];
      }

      $fields[$this->target_field_id]->addValue($weight);
    }*/
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
    $parent_name = 'processors[role][settings]';
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
        $this->t('Role'),
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

    $roles = array_map(function (RoleInterface $role) {
      return Html::escape($role->label());
    }, user_roles());

    // Loop over each role and create a form row.
    foreach ($roles as $role_id => $role_name) {
      $weight = $this->configuration['weight'];
      if ($this->configuration['sorttable'][$role_id]['weight']) {
        $weight = $this->configuration['sorttable'][$role_id]['weight'];
      }

      // Add form with weights
      // Mark the table row as draggable.
      $form['sorttable'][$role_id]['#attributes']['class'][] = 'draggable';

      // Sort the table row according to its existing/configured weight.
      // TODO Check why the rows are not sorted by weight.
      $form['sorttable'][$role_id]['#weight'] = $weight;

      // Table columns containing raw markup.
      $form['sorttable'][$role_id]['label']['#plain_text'] = $role_name;

      // Weight column element.
      $form['sorttable'][$role_id]['weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight for @title', ['@title' => $role_name]),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        // Classify the weight element for #tabledrag.
        '#attributes' => ['class' => ['sorttable-order-weight']],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    // Automatically add field to index if processor is enabled.
    $field = $this->ensureField(NULL, $this->target_field_id, 'integer');
    // Hide the field.
    $field->setHidden();
  }
}
