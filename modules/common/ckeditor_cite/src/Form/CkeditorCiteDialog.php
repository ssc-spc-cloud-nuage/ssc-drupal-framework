<?php

namespace Drupal\ckeditor_cite\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Ajax\EditorDialogSave;
use Drupal\editor\Entity\Editor;

/**
 * Provides an cite dialog for text editors.
 */
class CkeditorCiteDialog extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ckeditor_cite_dialog';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Editor $editor = NULL) {
    // The default values are set directly from \Drupal::request()->request,
    // provided by the editor plugin opening the dialog.
    $user_input = $form_state->getUserInput();
    $input = $user_input['editor_object'] ?? [];

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'editor/drupal.editor.dialog';
    $form['#prefix'] = '<div id="ckeditor-cite-dialog-form">';
    $form['#suffix'] = '</div>';

    $form['attributes']['text'] = [
      '#title' => $this->t('Text'),
      '#type' => 'textfield',
      '#default_value' => $input['text'] ?? '',
    ];
    $form['attributes']['title'] = [
      '#title' => $this->t('Title'),
      '#type' => 'textfield',
      '#default_value' => $input['title'] ?? '',
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['save_modal'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#submit' => [],
      '#ajax' => [
        'callback' => '::submitForm',
        'event' => 'click',
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    if ($form_state->getErrors()) {
      unset($form['#prefix'], $form['#suffix']);

      $form['status_message'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];

      $response->addCommand(new HtmlCommand('#ckeditor-cite-dialog-form', $form));
    }
    else {
      $response->addCommand(new EditorDialogSave($form_state->getValues()));
      $response->addCommand(new CloseModalDialogCommand());
    }

    return $response;
  }

}
