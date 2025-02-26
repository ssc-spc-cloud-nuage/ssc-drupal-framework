<?php

namespace Drupal\ssc_common\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Filters nodes by their absolute URL.
 *
 * @ViewsFilter("absolute_url_filter")
 */
class AbsoluteUrlFilter extends FilterPluginBase {

  /**
   * Defines filter options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    // Define the default filter value.
    $options['value'] = ['default' => ''];

    // Define exposure options properly.
    $options['expose'] = [
      'default' => [
        'operator_id' => 'contains', // The default operator.
        'identifier' => 'absolute_url', // The machine name for the exposed filter.
        'label' => $this->t('Absolute URL'),
        'use_operator' => FALSE, // Don't expose the operator.
      ],
    ];

    return $options;
  }

  /**
   * Builds the exposed filter form.
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Absolute URL'),
      '#size' => 30,
      '#default_value' => $this->value ?? '',
    ];

    // Ensure Views correctly detects it as an exposed filter.
    $form['#type'] = 'container';
    $form['#attributes']['class'][] = 'views-exposed-widget';
  }

  /**
   * Processes the exposed filter input.
   */
  public function acceptExposedInput($input) {
    // Avoid undefined key error.
    if (isset($input['value']) && !empty($input['value'])) {
      $this->value = $input['value'];
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Modifies the query based on the filter value.
   */
  public function query() {
    if (!empty($this->value)) {
      // Alias for the node table in the Views query.
      $node_alias = $this->ensureMyTable();
      $field_name = 'nid'; // We filter using the Node ID.

      // Get node IDs where the absolute URL contains the filter value.
      $node_ids = [];
      $query = \Drupal::database()->select('node_field_data', 'nfd')
        ->fields('nfd', ['nid'])
        ->condition('nfd.status', 1); // Only published nodes.

      $result = $query->execute();
      foreach ($result as $record) {
        $node = Node::load($record->nid);
        if ($node) {
          $absolute_url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
          if (stripos($absolute_url, $this->value) !== FALSE) {
            $node_ids[] = $record->nid;
          }
        }
      }

      if (!empty($node_ids)) {
        // Modify the query to only fetch matching nodes.
        $this->query->addWhere(0, "$node_alias.$field_name", $node_ids, 'IN');
      }
      else {
        // If no matches, return an empty result.
        $this->query->addWhereExpression(0, '1 = 0');
      }
    }
  }
}
