<?php

namespace Drupal\ssc_search_boost\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\search_api\Plugin\PluginFormTrait;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;

/**
 * Stores exact match boost configuration for Search API DB indexes.
 *
 * @SearchApiProcessor(
 *   id = "ssc_exact_match_boost",
 *   label = @Translation("SSC exact match boost"),
 *   description = @Translation("Boosts exact matches in configured fields for Search API DB indexes."),
 *   stages = {}
 * )
 */
class SSCExactMatchBoost extends FieldsProcessorPluginBase implements PluginFormInterface {

  use PluginFormTrait;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'query_params' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['query_params'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search query parameter names'),
      '#description' => $this->t('Comma or space separated request parameter names containing the search text.'),
      '#default_value' => $this->configuration['query_params'] ?? '',
      '#required' => TRUE,
    ];

    // Hide "all fields" if present.
    $form['all_fields']['#access'] = FALSE;

    // Limit selectable fields.
    $allowed = ['string', 'text', 'solr_text_custom'];

    foreach ($this->index->getFields() as $field) {
      [$type] = explode(':', $field->getType());

      if (!in_array($type, $allowed, TRUE)) {
        unset($form['fields']['#options'][$field->getFieldIdentifier()]);
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $fields = array_filter($form_state->getValue('fields') ?? []);

    if (empty($fields)) {
      $form_state->setErrorByName('fields', $this->t('Select at least one exact match field.'));
    }

    if (trim((string) $form_state->getValue('query_params')) === '') {
      $form_state->setErrorByName('query_params', $this->t('Enter at least one search query parameter name.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    $values = $form_state->getValues();

    $this->configuration['query_params'] = trim((string) ($values['query_params'] ?? ''));
  }

  /**
   * Converts stored boost fields to textarea format.
   */
  protected function formatBoostFields(array $boost_fields): string {
    $lines = [];

    foreach ($boost_fields as $field => $settings) {
      $weight = $settings['weight'] ?? 100000;
      $lines[] = $field . '|' . $weight;
    }

    return implode("\n", $lines);
  }

  /**
   * Parses textarea config.
   */
  protected function parseBoostFields(string $value): array {
    $boost_fields = [];

    $lines = preg_split('/\r\n|\r|\n/', $value);

    foreach ($lines as $line) {
      $line = trim($line);

      if ($line === '') {
        continue;
      }

      [
        $field,
        $weight
      ] = array_pad(array_map('trim', explode('|', $line, 2)), 2, 100000);

      if ($field === '') {
        continue;
      }

      $boost_fields[$field] = [
        'weight' => max(1, (int) $weight),
      ];
    }

    return $boost_fields;
  }

}
