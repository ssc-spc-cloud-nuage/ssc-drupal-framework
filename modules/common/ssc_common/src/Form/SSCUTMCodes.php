<?php

namespace Drupal\ssc_common\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures module settings.
 */
class SSCUTMCodes extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ssc_common_utm_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ssc_common.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ssc_common.settings');

    // Add the 'utm_codes' form details type named 'Home Page'.
    $form['utm_codes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Home Page'),
      '#open' => TRUE,
      '#attributes' => ['class' => ['no-toggle']],
    ];

    $fields = [
      'requested_blocks' => t('Most requested'),
      'promo_blocks' => t('Promotional blocks'),
      'upcoming_events' => t('Upcoming Events'),
      'latest_news' => t('Latest News'),
    ];

    foreach ($fields as $key => $label) {
      $form['utm_codes'][$key] = [
        '#type' => 'details',
        '#title' => $label,
        '#open' => $key === 'requested_blocks',
      ];

      $utm_fields = [
        'utm_source' => $this->t('UTM Source'),
        'utm_medium' => $this->t('UTM Medium'),
        'utm_campaign' => $this->t('UTM Campaign'),
        'utm_content' => $this->t('UTM Content'),
        'utm_term' => $this->t('UTM Term'),
      ];

      foreach ($utm_fields as $field_key => $field_label) {
        switch ($field_key) {
          case 'utm_source':
            $description = $this->t('Source of access to the link (e.g. home page, gazette, news)');
            break;
          case 'utm_medium':
            $description = $this->t('Medium used to share link (e.g. email, specific page, page section, etc)');
            break;
          case 'utm_campaign':
            $description = $this->t('Name of the campaign (e.g. GCWCC, Ask me anything, etc.)');
            break;
          case 'utm_content':
            $description = $this->t('Call to action or headline used.');
            break;
          case 'utm_term':
            $description = $this->t('Keywords / related topics used.');
            break;
          default:
            $description = '';
            break;
        }
        $form['utm_codes'][$key][$key . '_' . $field_key] = [
          '#type' => 'textfield',
          '#title' => $field_label,
          '#description' => $description,
          '#default_value' => $config->get($key . '_' . $field_key),
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->config('ssc_common.settings');

    $fields = [
      'requested_blocks',
      'promo_blocks',
      'upcoming_events',
      'latest_news',
    ];

    foreach ($fields as $key) {
      foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $field_key) {
        $config->set($key . '_' . $field_key, $form_state->getValue($key . '_' . $field_key))->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $fields_to_check = [
      'requested_blocks',
      'promo_blocks',
      'upcoming_events',
      'latest_news',
    ];

    foreach ($fields_to_check as $key) {
      foreach (['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term'] as $field_key) {
        $field_value = $form_state->getValue($key . '_' . $field_key);
        if (strpos($field_value, ' ') !== false) {
          $form_state->setErrorByName($key . '_' . $field_key, t('@field_name should not contain spaces.', ['@field_name' => $form[$key][$key . '_' . $field_key]['#title']]));
        }
      }
    }
  }

}
