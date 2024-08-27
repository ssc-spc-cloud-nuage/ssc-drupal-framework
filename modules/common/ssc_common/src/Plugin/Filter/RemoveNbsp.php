<?php

namespace Drupal\ssc_common\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Delete all &nbsp; and whitespace from TDs and DIVs.
 *
 * @Filter(
 *   id = "remove_nbsp",
 *   module = "ssc_common",
 *   title = @Translation("Remove NBSP"),
 *   description = @Translation("Remove NBSP and whitespace from empty TDs and DIVs."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   settings = {
 *     "tags" = "td div"
 *   },
 * )
 */
class RemoveNbsp extends FilterBase {
  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult(
      $this->removeNBSP($text) ?? FALSE
    );
  }

  /**
   * {@inheritdoc}
   */
  public function tips($long = FALSE) {
    $tips = [];
    $tips[] = t('Remove NBSP and whitespace from empty TDs and DIVs.');

    if ($long) {
      $tips[] = t('Remove NBSP and whitespace from empty TDs and DIVs.');
    }

    return implode(' ', $tips);
  }

  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['tags'] = [
      '#type' => 'textfield',
      '#title' => t('Tags'),
      '#default_value' => $this->settings['tags'] ?? 'td div',
      '#required' => TRUE,
      '#description' => t('Tags to remove whitespace, separate each tag with space.'),
    ];

    return $form;
  }

  private function removeNBSP($text) {
    $values = $this->settings['tags'];
    $array_tags = explode(' ', $values);
    $tags = implode('|', $array_tags);
    $pattern = '/<(' . $tags . ')>\s*&nbsp;\s*<\/\1>|<(' . $tags . ')>\s*<\/\1>/i';
    $replacement = '<$1></$1>';
    return preg_replace($pattern, $replacement, $text);
  }

}
