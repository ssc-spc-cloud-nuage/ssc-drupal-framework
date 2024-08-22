<?php

namespace Drupal\ssc_common\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Delete all &nbsp; and whitespace from TDs and DIVs.
 *
 * @Filter(
 *   id = "remove_nbsp",
 *   module = "ssc_common",
 *   title = @Translation("Remove NBSP"),
 *   description = @Translation("Remove NBSP and whitespace from empty TDs and DIVs."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
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

  private function removeNBSP($text) {
    $pattern = '/<(td|div)>\s*&nbsp;\s*<\/\1>|<(td|div)>\s*<\/\1>/i';
    $replacement = '<$1></$1>';
    return preg_replace($pattern, $replacement, $text);
  }

}
