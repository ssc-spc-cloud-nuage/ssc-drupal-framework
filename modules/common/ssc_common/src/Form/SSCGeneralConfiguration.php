<?php

namespace Drupal\ssc_common\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form that configures module settings.
 *
 *  - to use config later:
 * $config = \Drupal::config('ssc_common.settings');
 * $config->get('some_config_value')
 *
 */
class SSCGeneralConfiguration extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ssc_common_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ssc_common.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ssc_common.settings');

    // Application Cloning - mapping fields for app clone
    $form['login'] = [
      '#type' => 'details',
      '#title' => t('Login Page'),
      '#open' => FALSE,
    ];
    $form['login']['login_page_h1'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Login page title (H1)'),
      '#description' => $this->t('Used as the H1 on the Login page.'),
      '#default_value' => $config->get('login_page_h1'),
    ];
    $form['login']['login_page_info'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Login page information text'),
      '#description' => $this->t('Information text following the login button on the Login page.'),
      '#default_value' => $config->get('login_page_info'),
    ];

    $form['footer_aside'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Footer aside content'),
      '#description' => $this->t('Content added to the aside section of the footer.'),
      '#default_value' => $config->get('footer_aside.value'),
      '#format' => $config->get('footer_aside.format'),
    ];

    $form['comment_login_info'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Comment login info'),
      '#description' => $this->t('Information text above the Login button for Anonymous users.'),
      '#default_value' => $config->get('comment_login_info.value'),
      '#format' => $config->get('comment_login_info.format'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('ssc_common.settings')
      ->set('login_page_h1', $form_state->getValue('login_page_h1'))
      ->set('login_page_info', $form_state->getValue('login_page_info'))
      ->set('footer_social', $form_state->getValue('footer_social'))
      ->set('footer_aside', $form_state->getValue('footer_aside'))
      ->set('comment_login_info', $form_state->getValue('comment_login_info'))
      ->save();
  }

}
